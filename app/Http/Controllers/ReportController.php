<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use App\Services\SpendWiseAssistant;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function __construct(private readonly ReportDataService $reports) {}

    public function index(Request $request): View
    {
        [$startDate, $endDate] = $this->dateRange($request);

        return view('reports.index', [
            'report' => $this->reports->forRange($request->user(), $startDate, $endDate),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ]);
    }

    public function assistant(Request $request, SpendWiseAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        [$startDate, $endDate] = $this->dateRange($request);
        $report = $this->reports->forRange($request->user(), $startDate, $endDate);

        return response()->json([
            'answer' => $assistant->respond(
                $validated['question'],
                $report,
                $startDate->format('M j, Y').' to '.$endDate->format('M j, Y'),
                $request->user()->name
            ),
        ]);
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $today = now()->startOfDay();
        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : $today->copy()->startOfMonth()->subMonths(5);
        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->startOfDay()
            : $today;

        if ($startDate->greaterThan($endDate)) {
            throw ValidationException::withMessages([
                'start_date' => ['The start date must be on or before the end date.'],
            ]);
        }

        return [$startDate, $endDate];
    }
}
