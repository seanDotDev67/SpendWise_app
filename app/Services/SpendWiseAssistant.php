<?php

namespace App\Services;

use Illuminate\Support\Str;

class SpendWiseAssistant
{
    public function __construct(private readonly GeminiReportResponder $gemini) {}

    public function respond(string $question, array $report, string $rangeLabel, string $userName): string
    {
        $conversationalReply = $this->conversationalReply($question, $userName);
        if ($conversationalReply !== null) {
            return $conversationalReply;
        }

        $verifiedAnswer = $this->verifiedAnswer($question, $report, $rangeLabel);
        $explanation = $this->gemini->explain($question, $report, $rangeLabel);

        return $explanation
            ? $verifiedAnswer."\n\n".$explanation
            : $verifiedAnswer;
    }

    private function conversationalReply(string $question, string $userName): ?string
    {
        $normalized = Str::of($question)->lower()->trim()->replaceMatches('/[^\pL\pN\s]+/u', '')->squish()->toString();

        if (preg_match('/^(?:hi|hello|hey|hiya|howdy|hi there|hello there|hey there|good morning|good afternoon|good evening|how are you|how are you doing|hows it going|whats up)$/', $normalized)) {
            $greeting = match (true) {
                now()->hour < 12 => 'Good morning',
                now()->hour < 18 => 'Good afternoon',
                default => 'Good evening',
            };
            $firstName = preg_split('/\s+/', trim($userName))[0] ?: 'there';

            return $greeting.', '.$firstName.'! 👋 I’m glad you stopped by. I’m SpendWise AI, and I’m here to help make sense of your finances. Want to look at your spending, see which categories are adding up, or talk about ways to save?';
        }

        if (preg_match('/^(?:thanks|thank you|thanks a lot|thank you so much|that helps|thats helpful)$/', $normalized)) {
            return 'You’re very welcome! I’m glad I could help. If you want to explore another part of your spending, just ask and we can look at it together.';
        }

        return null;
    }

    private function verifiedAnswer(string $question, array $report, string $rangeLabel): string
    {
        $question = Str::lower($question);
        $topCategory = $report['categories']->first();
        $money = fn (float $amount): string => '₱'.number_format($amount, 2);

        if (Str::contains($question, ['budget', 'within my budget', 'over budget'])) {
            return 'I’d be happy to check that, but budgets aren’t saved in SpendWise yet, so I can’t tell whether you’re within one. I can still walk you through your recorded income and spending for '.$rangeLabel.'.';
        }

        if (Str::contains($question, ['category', 'categories', 'highest', 'top spending'])) {
            if (! $topCategory) {
                return 'I checked your report, but there aren’t any expenses recorded for '.$rangeLabel.' yet. Once you add some, I can help spot which category is taking the biggest share.';
            }

            return 'Looks like '.$topCategory['name'].' is your biggest spending category for '.$rangeLabel.': '.$money($topCategory['spent']).' ('.$topCategory['percentage'].'% of recorded expenses). Want to explore what’s included in that category?';
        }

        if (Str::contains($question, ['trend', 'trends', 'over time', 'month to month'])) {
            if ($report['transactionCount'] === 0) {
                return 'I’d love to compare your trends, but there aren’t any transactions recorded for '.$rangeLabel.' yet. Add some activity and I can help you see how things change over time.';
            }

            $months = $report['monthly']->map(fn (array $month): string => $month['label'].': income '.$money($month['income']).', expenses '.$money($month['expenses']).', net '.$money($month['net']))->implode('; ');

            return 'Sure, let’s take a look. Here are your recorded monthly totals for '.$rangeLabel.': '.$months.'. The comparison uses only activity in your selected date range.';
        }

        if (Str::contains($question, ['save', 'saving', 'reduce', 'cut back', 'spend less'])) {
            if (! $topCategory) {
                return 'I’d be happy to brainstorm ways to save, but there isn’t any spending recorded for '.$rangeLabel.' to personalize a suggestion yet. Once there’s some activity, we can look for a good place to start.';
            }

            return 'A good place to start might be '.$topCategory['name'].', your largest recorded expense category at '.$money($topCategory['spent']).' for '.$rangeLabel.'. You could review recurring purchases there and see whether anything is easy to trim. I won’t guess at savings, but I can help you think through options.';
        }

        if (Str::contains($question, ['cash flow', 'net', 'income', 'expense', 'spend', 'spent', 'spending', 'much'])) {
            return 'Absolutely — for '.$rangeLabel.', you recorded '.$money($report['income']).' in income and '.$money($report['expenses']).' in expenses, leaving a net cash flow of '.$money($report['net']).' across '.$report['transactionCount'].' transactions. Is there a category or month you’d like to dig into?';
        }

        return 'I’m happy to help! I can look at your spending total, find your biggest expense category, walk through monthly trends, or brainstorm ways to save. What would you like to explore? I’ll use your selected report range and won’t guess if information is missing.';
    }
}
