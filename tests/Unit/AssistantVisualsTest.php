<?php

namespace Tests\Unit;

use App\Services\AssistantVisuals;
use PHPUnit\Framework\TestCase;

class AssistantVisualsTest extends TestCase
{
    private function context(array $extra = []): array
    {
        return [
            'recorded_totals' => ['income' => '5000.00', 'expenses' => '1800.50'],
            'expense_categories' => [
                ['name' => 'Food', 'spent' => '1000.00', 'share_percent' => 56],
                ['name' => 'Transport', 'spent' => '800.50', 'share_percent' => 44],
            ],
            'monthly_totals' => [
                ['month' => 'Sep 2026', 'income' => '4000.00', 'expenses' => '1500.00'],
                ['month' => 'Oct 2026', 'income' => '5000.00', 'expenses' => '1800.50'],
            ],
        ] + $extra;
    }

    public function test_no_visuals_without_financial_context(): void
    {
        $this->assertSame([], (new AssistantVisuals)->fromContext(null, 'hello'));
    }

    public function test_default_question_gets_summary_bars_and_category_donut(): void
    {
        $visuals = (new AssistantVisuals)->fromContext($this->context(), 'How much did I spend?');

        $this->assertSame(['comparison', 'doughnut'], array_column($visuals, 'type'));
        $this->assertSame([5000.0, 1800.5], $visuals[0]['datasets'][0]['data']);
        $this->assertSame(['Food', 'Transport'], $visuals[1]['labels']);
    }

    public function test_trend_question_gets_a_line_chart(): void
    {
        $visuals = (new AssistantVisuals)->fromContext($this->context(), 'Show my spending trend over time');

        $this->assertSame(['line'], array_column($visuals, 'type'));
        $this->assertSame(['Sep 2026', 'Oct 2026'], $visuals[0]['labels']);
    }

    public function test_comparison_context_gets_period_and_category_bars(): void
    {
        $context = $this->context([
            'previous_period' => ['income' => '4000.00', 'expenses' => '1500.00'],
            'category_comparison' => [
                ['name' => 'Food', 'current_spent' => '1000.00', 'previous_spent' => '600.00'],
            ],
        ]);

        $visuals = (new AssistantVisuals)->fromContext($context, 'Compare to last month');

        $this->assertSame(['comparison', 'comparison'], array_column($visuals, 'type'));
        $this->assertSame([600.0], $visuals[1]['datasets'][0]['data']);
        $this->assertSame([1000.0], $visuals[1]['datasets'][1]['data']);
    }
}
