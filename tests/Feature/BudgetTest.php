<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_page_shows_the_users_categories_limits_and_monthly_expense_totals(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00'));
        $user = User::factory()->create();
        $category = $user->categories()->create([
            'name' => 'Groceries',
            'type' => 'expense',
            'color' => '#123abc',
            'icon' => 'basket',
        ]);
        Budget::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 2500,
        ]);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 875.50,
            'type' => 'expense',
            'date' => '2026-10-12',
        ]);

        $response = $this->actingAs($user)->get(route('budgets.index'));

        $response->assertOk()
            ->assertSee('Groceries')
            ->assertSee('"color":"#123abc"', false)
            ->assertSee('"limit":2500', false)
            ->assertSee('"2026-10":875.5', false);
    }

    public function test_budget_create_update_and_delete_persist_for_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Food', 'type' => 'expense']);

        $this->actingAs($user)
            ->post(route('budgets.store'), ['category_id' => $category->id, 'amount' => 1200])
            ->assertRedirect(route('budgets.index'));

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 1200,
        ]);

        $this->put(route('budgets.update', $category), ['amount' => 1500])
            ->assertRedirect(route('budgets.index'));
        $this->assertDatabaseHas('budgets', ['category_id' => $category->id, 'amount' => 1500]);

        $this->delete(route('budgets.destroy', $category))
            ->assertRedirect(route('budgets.index'));
        $this->assertDatabaseMissing('budgets', ['category_id' => $category->id]);
    }

    public function test_budget_and_goal_routes_require_authentication(): void
    {
        $this->get(route('budgets.index'))->assertRedirect(route('login'));
        $this->post(route('budgets.store'))->assertRedirect(route('login'));
        $this->post(route('goals.store'))->assertRedirect(route('login'));
    }

    public function test_budget_routes_reject_categories_owned_by_another_user(): void
    {
        $category = User::factory()->create()
            ->categories()->create(['name' => 'Private', 'type' => 'expense']);

        $this->actingAs(User::factory()->create())
            ->post(route('budgets.store'), ['category_id' => $category->id, 'amount' => 1000])
            ->assertNotFound();

        $this->assertDatabaseMissing('budgets', ['category_id' => $category->id]);
    }
}
