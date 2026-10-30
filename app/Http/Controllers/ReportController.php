<?php

namespace App\Http\Controllers;

use App\Contracts\AssistantProvider;
use App\Services\AssistantProviderException;
use App\Services\AssistantResponseGuard;
use App\Services\FinancialInsightService;
use App\Services\ReportDataService;
use App\Services\SpendWiseAssistant;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportDataService $reports,
        private readonly AssistantProvider $provider
    ) {}

    public function index(Request $request): View
    {
        [$startDate, $endDate] = $this->dateRange($request);

        return view('reports.index', [
            'report' => $this->reports->forRange($request->user(), $startDate, $endDate),
            'goals' => $request->user()->goals()->orderBy('created_at')->get(),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'assistantHistory' => $request->session()->get($this->conversationKey($request), []),
            'assistantConfigured' => $this->provider->isConfigured(),
        ]);
    }

    public function assistant(Request $request, SpendWiseAssistant $assistant): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'string', Rule::in(array_keys(AssistantResponseGuard::PAGES))],
            'question' => ['required', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        [$startDate, $endDate] = $this->dateRange($request);

        if (! $this->provider->isConfigured()) {
            return response()->json([
                'message' => 'SpendWise AI is not configured correctly yet. Please ask the administrator to check the AI provider settings.',
            ], 503);
        }

        $key = $this->conversationKey($request);
        $history = $request->session()->get($key, []);
        $history = is_array($history) ? array_slice($history, -12) : [];
        try {
            $answer = $assistant->respond(
                $validated['question'],
                $history,
                $request->user(),
                $startDate,
                $endDate,
                $validated['page'] ?? null
            );
        } catch (AssistantProviderException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        if ($answer === null) {
            return response()->json([
                'message' => 'SpendWise AI could not respond right now. Please try again in a moment.',
            ], 503);
        }

        $history[] = ['role' => 'user', 'content' => $validated['question']];
        $history[] = ['role' => 'assistant', 'content' => $answer];
        $request->session()->put($key, array_slice($history, -12));

        return response()->json(['answer' => $answer]);
    }

    public function assistantStream(Request $request, SpendWiseAssistant $assistant): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'string', Rule::in(array_keys(AssistantResponseGuard::PAGES))],
            'question' => ['required', 'string', 'max:1000'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        [$startDate, $endDate] = $this->dateRange($request);

        if (! $this->provider->isConfigured()) {
            return response()->json([
                'message' => 'SpendWise AI is not configured correctly yet. Please ask the administrator to check the AI provider settings.',
            ], 503);
        }

        $key = $this->conversationKey($request);
        $history = $request->session()->get($key, []);
        $history = is_array($history) ? array_slice($history, -12) : [];
        $user = $request->user();
        $session = $request->session();

        return response()->stream(function () use ($assistant, $validated, $history, $user, $startDate, $endDate, $session, $key): void {
            $financialContext = null;
            $send = function (array $event): void {
                echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $answer = $assistant->stream(
                    $validated['question'],
                    $history,
                    $user,
                    $startDate,
                    $endDate,
                    fn (string $text) => $send(['type' => 'token', 'text' => $text]),
                    $validated['page'] ?? null,
                    $financialContext
                );
            } catch (AssistantProviderException $exception) {
                $send(['type' => 'error', 'message' => $exception->getMessage()]);

                return;
            }

            if ($answer === null) {
                $send(['type' => 'error', 'message' => 'SpendWise AI could not respond right now. Please try again in a moment.']);

                return;
            }

            $insight = app(FinancialInsightService::class)->build($financialContext, $validated['question'], $answer);
            $answer = $insight['message'] ?? $answer;
            $history[] = ['role' => 'user', 'content' => $validated['question']];
            // The insight is kept so the charts can be redrawn when the page reloads.
            $history[] = ['role' => 'assistant', 'content' => $answer, 'insight' => $insight];
            $session->put($key, array_slice($history, -12));
            // The session middleware has already finished by the time a streamed body runs.
            $session->save();

            $send([
                'type' => 'done',
                'answer' => $answer,
                'visuals' => $insight['charts'] ?? [],
                'insight' => $insight,
            ]);
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function resetAssistant(Request $request): JsonResponse
    {
        $request->session()->forget($this->conversationKey($request));

        return response()->json(['reset' => true]);
    }

    private function conversationKey(Request $request): string
    {
        return SpendWiseAssistant::HISTORY_KEY_PREFIX.$request->user()->getAuthIdentifier();
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
            : $today->copy()->startOfMonth();
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
