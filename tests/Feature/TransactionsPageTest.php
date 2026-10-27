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
}
