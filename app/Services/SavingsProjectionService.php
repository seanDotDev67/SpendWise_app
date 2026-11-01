<?php

namespace App\Services;

use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Deterministic savings projections. All money is handled as integer centavos so no
 * floating-point rounding can leak into a figure the assistant reports.
 *
 * Everything returned for a plan is hypothetical; nothing here reads or writes the database.
 */
class SavingsProjectionService
{
    /** Contribution periods per year for each supported frequency. */
    public const PERIODS_PER_YEAR = ['day' => 365, 'week' => 52, 'biweek' => 26, 'month' => 12, 'year' => 1];

    private const LABELS = [
        'day' => 'per day',
        'week' => 'per week',
        'biweek' => 'every 2 weeks',
        'month' => 'per month',
        'year' => 'per year',
    ];

    public static function label(string $frequency): string
    {
        return self::LABELS[$frequency] ?? 'per month';
    }

    /**
     * @return array<string, mixed>
     */
    public function project(
        float|string $target,
        float|string $saved,
        float|string $contribution,
        string $frequency,
        CarbonInterface $today,
        int $horizonMonths = 12,
        ?CarbonInterface $deadline = null
    ): array {
        if (! isset(self::PERIODS_PER_YEAR[$frequency])) {
            throw new InvalidArgumentException('Unsupported contribution frequency.');
        }

        $perYear = self::PERIODS_PER_YEAR[$frequency];
        $targetC = $this->cents($target);
        $savedC = $this->cents($saved);
        $amountC = max(0, $this->cents($contribution));
        $remainingC = max(0, $targetC - $savedC);
        $horizonMonths = max(1, $horizonMonths);

        $plan = [
            'is_hypothetical' => true,
            'status' => 'in_progress',
            'contribution_amount' => $this->money($amountC),
            'contribution_frequency' => self::label($frequency),
            'frequency' => $frequency,
            'actual_saved' => $this->money($savedC),
            'target' => $this->money($targetC),
            'remaining' => $this->money($remainingC),
            'horizon_months' => $horizonMonths,
        ];

        $inHorizon = intdiv($perYear * $horizonMonths, 12);
        $projectedC = $savedC + $inHorizon * $amountC;
        $plan += [
            'contributions_in_horizon' => $inHorizon,
            'projected_added' => $this->money($inHorizon * $amountC),
            'projected_saved' => $this->money($projectedC),
            'reaches_target_in_horizon' => $projectedC >= $targetC,
            'shortfall' => $this->money(max(0, $targetC - $projectedC)),
            'contributions_needed' => null,
            'months_needed' => null,
            'completion_date' => null,
        ];

        if ($remainingC === 0) {
            $plan['status'] = 'already_reached';
            $plan['contributions_needed'] = 0;
            $plan['months_needed'] = '0.0';
            $plan['reaches_target_in_horizon'] = true;
            $plan['shortfall'] = '0.00';

            return $plan;
        }

        if ($amountC === 0) {
            $plan['status'] = 'no_contribution';

            return $plan;
        }

        $needed = intdiv($remainingC + $amountC - 1, $amountC);
        $plan['contributions_needed'] = $needed;
        $plan['months_needed'] = $this->tenths(intdiv($needed * 240 + $perYear, 2 * $perYear));
        $plan['completion_date'] = $this->advance($today, $frequency, $needed)->toDateString();
        $plan['scenarios'] = $this->scenarios($remainingC, $amountC, $perYear, $frequency);
        $plan['timeline'] = $this->timeline($savedC, $targetC, $amountC, $perYear, (int) ceil($needed * 12 / $perYear));

        if ($deadline !== null) {
            $plan['deadline'] = $this->deadline($remainingC, $savedC, $targetC, $amountC, $frequency, $today, $deadline);
        }

        return $plan;
    }

    /**
     * Projected balance at milestone months, capped at the target, ending at the completion month.
     *
     * @return array<int, array{month: int, projected_saved: string}>
     */
    private function timeline(int $savedC, int $targetC, int $amountC, int $perYear, int $completionMonth): array
    {
        $months = array_values(array_filter([0, 3, 6, 12, 24, 36, 60], fn (int $m): bool => $m < $completionMonth));
        $months[] = $completionMonth;
        $months = array_slice($months, -7);

        return array_map(fn (int $m): array => [
            'month' => $m,
            'projected_saved' => $this->money($m === $completionMonth ? $targetC : min($targetC, $savedC + intdiv($perYear * $m, 12) * $amountC)),
        ], $months);
    }

    /**
     * Alternative contribution amounts, so the user can compare how fast each would finish the goal.
     *
     * @return array<int, array<string, mixed>>
     */
    private function scenarios(int $remainingC, int $amountC, int $perYear, string $frequency): array
    {
        $scenarios = [];
        foreach ([[1, 2], [1, 1], [3, 2], [2, 1]] as [$mul, $div]) {
            $scenarioC = intdiv($amountC * $mul, $div);
            if ($scenarioC <= 0) {
                continue;
            }
            $needed = intdiv($remainingC + $scenarioC - 1, $scenarioC);
            $scenarios[] = [
                'is_hypothetical' => true,
                'contribution_amount' => $this->money($scenarioC),
                'contributions_needed' => $needed,
                'months_needed' => $this->tenths(intdiv($needed * 240 + $perYear, 2 * $perYear)),
            ];
        }

        return $scenarios;
    }

    /**
     * @return array<string, mixed>
     */
    private function deadline(int $remainingC, int $savedC, int $targetC, int $amountC, string $frequency, CarbonInterface $today, CarbonInterface $deadline): array
    {
        $deadlineDay = $deadline->copy()->startOfDay();
        $daysLeft = (int) $today->copy()->startOfDay()->diffInDays($deadlineDay, false);
        $info = ['date' => $deadlineDay->toDateString(), 'days_left' => $daysLeft];

        if ($daysLeft <= 0) {
            return $info + ['status' => 'passed', 'periods_until_deadline' => 0];
        }

        $periods = match ($frequency) {
            'day' => $daysLeft,
            'week' => intdiv($daysLeft, 7),
            'biweek' => intdiv($daysLeft, 14),
            'month' => max(0, (int) floor($today->copy()->startOfDay()->diffInMonths($deadlineDay, false))),
            'year' => intdiv($daysLeft, 365),
        };

        $info['periods_until_deadline'] = $periods;
        if ($periods === 0) {
            return $info + ['status' => 'too_soon'];
        }

        $projectedC = $savedC + $periods * $amountC;
        $perPeriodWhole = intdiv($remainingC + $periods * 100 - 1, $periods * 100);

        return $info + [
            'status' => $projectedC >= $targetC ? 'on_track' : 'short',
            'projected_saved_at_deadline' => $this->money($projectedC),
            'shortfall_at_deadline' => $this->money(max(0, $targetC - $projectedC)),
            'surplus_at_deadline' => $this->money(max(0, $projectedC - $targetC)),
            'required_per_period' => $this->money(intdiv($remainingC * 2 + $periods, 2 * $periods)),
            'required_per_period_whole_pesos' => (string) $perPeriodWhole,
            'achievable_with_plan' => $projectedC >= $targetC,
        ];
    }

    private function advance(CarbonInterface $today, string $frequency, int $periods): CarbonInterface
    {
        $date = $today->copy()->startOfDay();

        return match ($frequency) {
            'day' => $date->addDays($periods),
            'week' => $date->addWeeks($periods),
            'biweek' => $date->addWeeks($periods * 2),
            'month' => $date->addMonthsNoOverflow($periods),
            'year' => $date->addYearsNoOverflow($periods),
        };
    }

    private function cents(float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function money(int $cents): string
    {
        return sprintf('%s%d.%02d', $cents < 0 ? '-' : '', intdiv(abs($cents), 100), abs($cents) % 100);
    }

    private function tenths(int $tenths): string
    {
        return intdiv($tenths, 10).'.'.($tenths % 10);
    }
}
