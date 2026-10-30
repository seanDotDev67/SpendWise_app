<?php

namespace App\Services;

/**
 * Validates and normalizes structured assistant payloads before they reach the UI.
 * Anything malformed or unsupported is dropped rather than rendered.
 */
class InsightResponseValidator
{
    public const CHART_TYPES = ['bar', 'line', 'doughnut', 'comparison', 'progress'];

    public const SEVERITIES = ['informational', 'warning', 'positive'];

    private const MAX_LABELS = 40;

    private const MAX_DATASETS = 4;

    private const MAX_ITEMS = 5;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null Null when there is no usable message.
     */
    public function response(array $payload): ?array
    {
        $message = $this->text($payload['message'] ?? null, 4000);
        if ($message === null) {
            return null;
        }

        $severity = $payload['severity'] ?? null;

        return [
            'message' => $message,
            'summary' => $this->text($payload['summary'] ?? null, 400) ?? '',
            'charts' => $this->charts(is_array($payload['charts'] ?? null) ? $payload['charts'] : []),
            'insights' => $this->strings($payload['insights'] ?? null),
            'recommendations' => $this->strings($payload['recommendations'] ?? null),
            'severity' => in_array($severity, self::SEVERITIES, true) ? $severity : 'informational',
        ];
    }

    /**
     * @param  array<int|string, mixed>  $charts
     * @return array<int, array<string, mixed>>
     */
    public function charts(array $charts): array
    {
        return array_values(array_filter(array_map(fn (mixed $chart): ?array => $this->chart($chart), $charts)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function chart(mixed $chart): ?array
    {
        if (! is_array($chart) || ! in_array($chart['type'] ?? null, self::CHART_TYPES, true)) {
            return null;
        }

        $title = $this->text($chart['title'] ?? null, 120);
        $labels = $chart['labels'] ?? null;
        $datasets = $chart['datasets'] ?? null;
        if ($title === null
            || ! is_array($labels) || ! array_is_list($labels) || $labels === [] || count($labels) > self::MAX_LABELS
            || ! is_array($datasets) || ! array_is_list($datasets) || $datasets === [] || count($datasets) > self::MAX_DATASETS) {
            return null;
        }

        $cleanLabels = [];
        foreach ($labels as $label) {
            if (! is_string($label) && ! is_int($label)) {
                return null;
            }
            $cleanLabels[] = mb_substr(trim((string) $label), 0, 60);
        }

        $cleanDatasets = [];
        foreach ($datasets as $dataset) {
            $clean = $this->dataset($dataset, count($cleanLabels));
            if ($clean === null) {
                return null;
            }
            $cleanDatasets[] = $clean;
        }

        $type = $chart['type'];
        if ($type === 'line' && count($cleanLabels) < 2) {
            return null;
        }
        if ($type === 'doughnut' || $type === 'progress') {
            $values = $cleanDatasets[0]['data'];
            if (count($cleanDatasets) !== 1 || min($values) < 0) {
                return null;
            }
            if ($type === 'doughnut' && array_sum($values) <= 0) {
                return null;
            }
            if ($type === 'progress' && max($values) > 1000) {
                return null;
            }
        }

        $result = [
            'type' => $type,
            'title' => $title,
            'labels' => $cleanLabels,
            'datasets' => $cleanDatasets,
            'kind' => ($chart['kind'] ?? null) === 'simulation' ? 'simulation' : 'actual',
        ];
        $note = $this->text($chart['note'] ?? null, 240);
        if ($note !== null) {
            $result['note'] = $note;
        }

        return $result;
    }

    /**
     * Extracts the explanation and recommendations from raw model output, which may be
     * plain text, a JSON object, or malformed JSON.
     *
     * @return array{message: string, recommendations: array<int, string>, severity: ?string}
     */
    public function parseModelOutput(string $output): array
    {
        $text = trim($output);
        $unfenced = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text);
        $plain = ['message' => $text, 'recommendations' => [], 'severity' => null];

        if (! str_starts_with($unfenced, '{')) {
            return $plain;
        }

        $decoded = json_decode($unfenced, true);
        if (is_array($decoded)) {
            $message = $this->text($decoded['message'] ?? null, 4000);
            if ($message === null) {
                return $plain;
            }

            return [
                'message' => $message,
                'recommendations' => $this->strings($decoded['recommendations'] ?? null),
                'severity' => in_array($decoded['severity'] ?? null, self::SEVERITIES, true) ? $decoded['severity'] : null,
            ];
        }

        // Truncated or otherwise broken JSON: salvage the message string if it is intact.
        if (preg_match('/"message"\s*:\s*("(?:[^"\\\\]|\\\\.)*")/s', $unfenced, $match) === 1) {
            $message = json_decode($match[1]);
            if (is_string($message) && trim($message) !== '') {
                return ['message' => trim($message), 'recommendations' => [], 'severity' => null];
            }
        }

        return $plain;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function dataset(mixed $dataset, int $labelCount): ?array
    {
        if (! is_array($dataset) || ! is_array($dataset['data'] ?? null) || ! array_is_list($dataset['data'])
            || count($dataset['data']) !== $labelCount) {
            return null;
        }

        $label = $this->text($dataset['label'] ?? null, 60);
        if ($label === null) {
            return null;
        }

        $data = [];
        foreach ($dataset['data'] as $value) {
            if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                return null;
            }
            $data[] = round((float) $value, 2);
        }

        $clean = ['label' => $label, 'data' => $data];
        if (is_string($dataset['color'] ?? null) && $this->isColor($dataset['color'])) {
            $clean['color'] = $dataset['color'];
        }
        if (is_array($dataset['colors'] ?? null) && array_is_list($dataset['colors'])
            && count($dataset['colors']) === $labelCount
            && count(array_filter($dataset['colors'], fn (mixed $c): bool => is_string($c) && $this->isColor($c))) === $labelCount) {
            $clean['colors'] = $dataset['colors'];
        }

        return $clean;
    }

    private function isColor(string $color): bool
    {
        return preg_match('/^#[0-9a-f]{6}$/i', $color) === 1;
    }

    private function text(mixed $value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }

    /**
     * @return array<int, string>
     */
    private function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn (mixed $item): ?string => $this->text($item, 300))
            ->filter()
            ->take(self::MAX_ITEMS)
            ->values()
            ->all();
    }
}
