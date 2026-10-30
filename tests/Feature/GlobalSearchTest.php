<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_pages_categories_and_only_own_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $category = Category::create(['user_id' => $user->id, 'name' => 'Groceries', 'type' => 'expense']);
        $otherCategory = Category::create(['user_id' => $other->id, 'name' => 'Groceries', 'type' => 'expense']);

        Transaction::create(['user_id' => $user->id, 'category_id' => $category->id, 'amount' => 12.5, 'type' => 'expense', 'description' => 'Grocery run', 'date' => '2026-01-02']);
        Transaction::create(['user_id' => $other->id, 'category_id' => $otherCategory->id, 'amount' => 99, 'type' => 'expense', 'description' => 'Grocery secret', 'date' => '2026-01-02']);

        $titles = collect($this->actingAs($user)->getJson(route('search', ['q' => 'groc']))->assertOk()->json('results'))->pluck('title');

        $this->assertTrue($titles->contains('Groceries'));
        $this->assertTrue($titles->contains('Grocery run'));
        $this->assertFalse($titles->contains('Grocery secret'));

        $pages = collect($this->actingAs($user)->getJson(route('search', ['q' => 'report']))->json('results'))->pluck('title');
        $this->assertTrue($pages->contains('Reports'));
    }

    public function test_search_requires_authentication(): void
    {
        $this->getJson(route('search', ['q' => 'a']))->assertUnauthorized();
    }
}
