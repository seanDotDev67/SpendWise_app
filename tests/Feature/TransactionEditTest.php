<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionEditTest extends TestCase
{
    use RefreshDatabase;

    private function makeTransaction(User $user, string $type = 'expense'): Transaction
    {
        $category = Category::create(['user_id' => $user->id, 'name' => 'Food', 'type' => $type]);

        return Transaction::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100,
            'type' => $type,
            'description' => 'Lunch',
            'date' => now()->toDateString(),
        ]);
    }

    public function test_history_row_has_edit_button_and_modal(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeTransaction($user);

        $this->actingAs($user)->get(route('transactions.page'))
            ->assertOk()
            ->assertSee('sw-edit-transaction', false)
            ->assertSee('editTransactionModal', false)
            ->assertSee('data-id="'.$tx->id.'"', false);
    }

    public function test_user_can_fix_category_and_amount(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeTransaction($user);
        $other = Category::create(['user_id' => $user->id, 'name' => 'Transport', 'type' => 'expense']);

        $this->actingAs($user)->put(route('transactions.update', $tx), [
            'type' => 'expense',
            'amount' => '250.50',
            'category_id' => $other->id,
            'description' => 'Fare',
            'date' => now()->toDateString(),
            '_edit_id' => $tx->id,
        ])->assertRedirect(route('transactions.page'))->assertSessionHas('status', 'Transaction updated.');

        $tx->refresh();
        $this->assertSame($other->id, $tx->category_id);
        $this->assertSame('250.50', (string) $tx->amount);
    }

    public function test_category_must_match_type_and_belong_to_user(): void
    {
        $user = User::factory()->create();
        $tx = $this->makeTransaction($user);
        $income = Category::create(['user_id' => $user->id, 'name' => 'Salary', 'type' => 'income']);
        $foreign = Category::create(['user_id' => User::factory()->create()->id, 'name' => 'X', 'type' => 'expense']);
        $payload = ['type' => 'expense', 'amount' => 10, 'description' => 'a', 'date' => now()->toDateString()];

        $this->actingAs($user)->put(route('transactions.update', $tx), $payload + ['category_id' => $income->id])
            ->assertSessionHasErrors('category_id');
        $this->put(route('transactions.update', $tx), $payload + ['category_id' => $foreign->id])
            ->assertSessionHasErrors('category_id');

        $this->assertSame('100.00', (string) $tx->fresh()->amount);
    }

    public function test_user_cannot_edit_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $tx = $this->makeTransaction($owner);
        $intruder = User::factory()->create();
        $cat = Category::create(['user_id' => $intruder->id, 'name' => 'Mine', 'type' => 'expense']);

        $this->actingAs($intruder)->put(route('transactions.update', $tx), [
            'type' => 'expense', 'amount' => 1, 'category_id' => $cat->id,
            'description' => 'x', 'date' => now()->toDateString(),
        ])->assertNotFound();

        $this->assertSame('100.00', (string) $tx->fresh()->amount);
    }
}
