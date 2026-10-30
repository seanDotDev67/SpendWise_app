<?php

namespace Tests\Unit;

use App\Services\InsightResponseValidator;
use PHPUnit\Framework\TestCase;

class InsightResponseValidatorTest extends TestCase
{
    private function chart(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'bar',
            'title' => 'Expenses by Category',
            'labels' => ['Food', 'Transport'],
            'datasets' => [['label' => 'Expenses', 'data' => [4200, 1800.456]]],
        ];
    }

    public function test_a_valid_chart_is_normalized(): void
    {
        $chart = (new InsightResponseValidator)->chart($this->chart());

        $this->assertSame('bar', $chart['type']);
        $this->assertSame([4200.0, 1800.46], $chart['datasets'][0]['data']);
        $this->assertSame('actual', $chart['kind']);
    }

    public function test_unsupported_types_and_malformed_charts_are_rejected(): void
    {
        $validator = new InsightResponseValidator;

        $this->assertNull($validator->chart($this->chart(['type' => 'pie'])));
        $this->assertNull($validator->chart($this->chart(['type' => '<script>'])));
        $this->assertNull($validator->chart($this->chart(['title' => ''])));
        $this->assertNull($validator->chart($this->chart(['labels' => []])));
        $this->assertNull($validator->chart($this->chart(['datasets' => [['label' => 'x', 'data' => [1]]]])));
        $this->assertNull($validator->chart($this->chart(['datasets' => [['label' => 'x', 'data' => ['1', 'abc']]]])));
        $this->assertNull($validator->chart($this->chart(['datasets' => [['label' => 'x', 'data' => [1, INF]]]])));
        $this->assertNull($validator->chart('not a chart'));
    }

    public function test_type_specific_rules_are_enforced(): void
    {
        $validator = new InsightResponseValidator;

        $this->assertNull($validator->chart($this->chart(['type' => 'doughnut', 'datasets' => [['label' => 'x', 'data' => [-1, 5]]]])));
        $this->assertNull($validator->chart($this->chart(['type' => 'doughnut', 'datasets' => [['label' => 'x', 'data' => [0, 0]]]])));
        $this->assertNull($validator->chart($this->chart(['type' => 'progress', 'datasets' => [['label' => 'x', 'data' => [50, 5000]]]])));
        $this->assertNull($validator->chart($this->chart(['type' => 'line', 'labels' => ['Jan'], 'datasets' => [['label' => 'x', 'data' => [1]]]])));
        $this->assertNotNull($validator->chart($this->chart(['type' => 'progress', 'datasets' => [['label' => 'x', 'data' => [50, 120]]]])));
    }

    public function test_invalid_colors_are_dropped(): void
    {
        $chart = (new InsightResponseValidator)->chart($this->chart([
            'datasets' => [['label' => 'x', 'data' => [1, 2], 'color' => 'url(javascript:alert(1))']],
        ]));

        $this->assertArrayNotHasKey('color', $chart['datasets'][0]);
    }

    public function test_response_requires_a_message_and_defaults_severity(): void
    {
        $validator = new InsightResponseValidator;

        $this->assertNull($validator->response(['summary' => 'No message']));

        $response = $validator->response([
            'message' => 'Hello',
            'severity' => 'catastrophic',
            'charts' => [$this->chart(), $this->chart(['type' => 'radar'])],
            'insights' => ['One', '', 5],
        ]);

        $this->assertSame('informational', $response['severity']);
        $this->assertCount(1, $response['charts']);
        $this->assertSame(['One'], $response['insights']);
        $this->assertSame([], $response['recommendations']);
    }

    public function test_model_output_parsing_handles_plain_fenced_and_broken_json(): void
    {
        $validator = new InsightResponseValidator;

        $this->assertSame('Just text.', $validator->parseModelOutput('Just text.')['message']);

        $fenced = $validator->parseModelOutput("```json\n{\"message\":\"Hi\",\"recommendations\":[\"Save\"],\"severity\":\"positive\"}\n```");
        $this->assertSame(['Hi', ['Save'], 'positive'], [$fenced['message'], $fenced['recommendations'], $fenced['severity']]);

        $broken = $validator->parseModelOutput('{"message":"Intact text.","recommendations":["cut');
        $this->assertSame('Intact text.', $broken['message']);

        $hopeless = $validator->parseModelOutput('{"oops": ');
        $this->assertSame('{"oops":', $hopeless['message']);
    }
}
