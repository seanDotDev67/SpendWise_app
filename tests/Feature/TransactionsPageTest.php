<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_page_is_empty_and_has_an_animated_page_shell(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('transactions.page'));

        $response->assertOk()
            ->assertSee('Transactions')
            ->assertSee('sw-page-content')
            ->assertSee('sw-page-enter')
            ->assertSee('prefers-reduced-motion')
        ->assertSee('aria-current="page"', false)
        ->assertDontSee('Recent transactions')
        ->assertDontSee('No transactions');
    }

    public function test_transactions_page_requires_authentication(): void
    {
        $this->get(route('transactions.page'))
            ->assertRedirect(route('login'));
    }

    public function test_transactions_json_index_remains_available(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('transactions.index'))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_transactions_page_shows_the_signed_in_users_balance_categories_and_history(): void
    {
        $user = User::factory()->create(['name' => 'Transaction User']);
        $category = $user->categories()->create([
            'name' => 'Groceries',
            'type' => 'expense',
        ]);
        $user->transactions()->create([
            'category_id' => $category->id,
            'amount' => 125.50,
            'type' => 'expense',
            'description' => 'Weekly shop',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('transactions.page'))
            ->assertOk()
            ->assertSee('Groceries')
            ->assertSee('Weekly shop')
            ->assertSee('₱ -125.50')
            ->assertSee('data-balance="-125.50"', false)
            ->assertSee('Transaction User');
    }

    public function test_transaction_form_persists_income_expense_details_and_returns_the_category(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create([
            'name' => 'Transport',
            'type' => 'expense',
        ]);

        $this->actingAs($user)
            ->postJson(route('transactions.store'), [
                'category_id' => $category->id,
                'amount' => '42.75',
                'type' => 'expense',
                'description' => 'Bus fare',
                'date' => '2026-10-28',
                'payment_method' => 'Cash',
                'notes' => 'Morning commute',
            ])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Transport')
            ->assertJsonPath('payment_method', 'Cash')
            ->assertJsonPath('notes', 'Morning commute');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => '42.75',
            'type' => 'expense',
            'description' => 'Bus fare',
            'payment_method' => 'Cash',
            'notes' => 'Morning commute',
        ]);
    }

    public function test_transaction_store_rejects_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $otherUser->categories()->create([
            'name' => 'Private',
            'type' => 'expense',
        ]);

        $this->actingAs($user)
            ->postJson(route('transactions.store'), [
                'category_id' => $category->id,
                'amount' => 10,
                'type' => 'expense',
                'description' => 'Not allowed',
                'date' => '2026-10-28',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
    }
}
