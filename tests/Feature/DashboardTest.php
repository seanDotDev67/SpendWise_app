<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_responsive_navigation_and_animated_summary_cards(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSee('class="sw-viewport"', false)
            ->assertDontSee('id="swFit"', false)
            ->assertDontSee("matchMedia('(min-width: 1200px)')", false)
            ->assertSee('sw-sidebar')
            ->assertSee('width: 180px', false)
            ->assertSee('position: fixed', false)
            ->assertSee('position: sticky', false)
            ->assertSee('sw-topbar')
            ->assertSee('sw-mobile-nav')
            ->assertSee('min-height: 100vh', false)
            ->assertSee('sw-entrance')
            ->assertSee('sw-donut-sweep')
            ->assertSee('mask: conic-gradient', false)
            ->assertSee('Monthly cash flow')
            ->assertSee('prefers-reduced-motion')
            ->assertSee(route('profile.edit'), false)
            ->assertSee(route('transactions.page'), false)
            ->assertSee(route('logout'), false)
            ->assertSee('Log out')
            ->assertSee('Recent transactions')
            ->assertSee('No expenses recorded this month.');
    }

    public function test_calendar_highlights_today_in_the_application_timezone(): void
    {
        config(['app.timezone' => 'Asia/Manila']);
        $this->travelTo(Carbon::parse('2026-10-25 16:30:00', 'UTC'));

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('October 2026')
            ->assertSee('aria-current="date" data-date="2026-10-26"', false);
    }

    public function test_dashboard_converts_category_palette_names_to_colors_in_both_spending_charts(): void
    {
        $user = User::factory()->create();
        $category = DB::table('categories')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'color' => 'forest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            'user_id' => $user->id,
            'category_id' => $category,
            'amount' => 1200,
            'type' => 'expense',
            'description' => 'Groceries',
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('style="--sw-donut-gradient: conic-gradient(#3b4a3c 0% 100%)"', false)
            ->assertSee('background: #3b4a3c', false)
            ->assertDontSee('background: forest', false);
    }

    public function test_dashboard_add_transaction_quick_action_opens_shared_form_and_returns_after_saving(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create([
            'name' => 'Food',
            'type' => 'expense',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-bs-target="#addTransactionModal"', false)
            ->assertSee('id="addTransactionModal"', false)
            ->assertSee('name="return_to" value="dashboard"', false)
            ->assertSee('Food');

        $this->post(route('transactions.store'), [
            'category_id' => $category->id,
            'amount' => '25.50',
            'type' => 'expense',
            'description' => 'Lunch',
            'date' => now()->toDateString(),
            'return_to' => 'dashboard',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'description' => 'Lunch',
            'amount' => '25.50',
        ]);
    }

    public function test_dashboard_totals_and_spending_are_scoped_to_the_signed_in_user(): void
    {
        $user = User::factory()->create(['name' => 'Dashboard User']);
        $otherUser = User::factory()->create();
        $food = DB::table('categories')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Food',
            'type' => 'expense',
            'color' => '#2F4A3A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $salary = DB::table('categories')->insertGetId([
            'user_id' => $user->id,
            'name' => 'Salary',
            'type' => 'income',
            'color' => '#7F9468',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherCategory = DB::table('categories')->insertGetId([
            'user_id' => $otherUser->id,
            'name' => 'Other user category',
            'type' => 'expense',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('transactions')->insert([
            [
                'user_id' => $user->id,
                'category_id' => $salary,
                'amount' => 5000.25,
                'type' => 'income',
                'description' => 'Monthly salary',
                'date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'category_id' => $food,
                'amount' => 1200.50,
                'type' => 'expense',
                'description' => 'Groceries',
                'date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'category_id' => $salary,
                'amount' => 2000,
                'type' => 'income',
                'description' => 'Previous month income',
                'date' => now()->subMonthNoOverflow()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'category_id' => $food,
                'amount' => 400,
                'type' => 'expense',
                'description' => 'Previous month groceries',
                'date' => now()->subMonthNoOverflow()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $otherUser->id,
                'category_id' => $otherCategory,
                'amount' => 7000,
                'type' => 'expense',
                'description' => 'Private transaction',
                'date' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('₱ 5,000.25')
            ->assertSee('₱ 1,200.50')
            ->assertSee('₱ 5,399.75')
            ->assertSee('Food')
            ->assertSee('Groceries')
            ->assertDontSee('Private transaction')
            ->assertDontSee('7,000.00');
    }

    public function test_dashboard_coach_gives_grounded_saving_guidance_from_monthly_activity(): void
    {
        $user = User::factory()->create();
        $incomeCategory = $user->categories()->create(['name' => 'Salary', 'type' => 'income']);
        $expenseCategory = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);

        $user->transactions()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 5000,
            'type' => 'income',
            'description' => 'Payday',
            'date' => now()->toDateString(),
        ]);
        $user->transactions()->create([
            'category_id' => $expenseCategory->id,
            'amount' => 1200,
            'type' => 'expense',
            'description' => 'Groceries',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SpendWise AI Coach')
            ->assertSee('There may be room to save')
            ->assertSee('₱3,800.00 more recorded income than expenses')
            ->assertSee('consider setting aside a comfortable portion')
            ->assertSee('Food')
            ->assertSee('₱1,200.00')
            ->assertSee('Guidance uses recorded transactions only.');
    }

    public function test_dashboard_coach_does_not_assume_affordability_when_income_is_unrecorded(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Transport', 'type' => 'expense']);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 40,
            'type' => 'expense',
            'description' => 'Fare',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Let’s get a fuller picture')
            ->assertSee('I can’t tell whether your spending is affordable')
            ->assertSee('keep essentials a priority')
            ->assertSee('Transport');
    }

    public function test_dashboard_coach_handles_expenses_above_recorded_income_without_judgment(): void
    {
        $user = User::factory()->create();
        $incomeCategory = $user->categories()->create(['name' => 'Salary', 'type' => 'income']);
        $expenseCategory = $user->categories()->create(['name' => 'Housing', 'type' => 'expense']);

        $user->transactions()->create([
            'category_id' => $incomeCategory->id,
            'amount' => 1000,
            'type' => 'income',
            'description' => 'Payday',
            'date' => now()->toDateString(),
        ]);
        $user->transactions()->create([
            'category_id' => $expenseCategory->id,
            'amount' => 1200,
            'type' => 'expense',
            'description' => 'Rent',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Spending is ahead of recorded income')
            ->assertSee('₱200.00 more than your recorded income')
            ->assertSee('This may be a timing issue or missing income')
            ->assertSee('before making cuts to essentials')
            ->assertSee('Housing');
    }
}
