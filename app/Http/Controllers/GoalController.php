<?php
// app/Http/Controllers/GoalController.php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    private function back(string $msg)
    {
        return redirect()->route('budgets.index')->with('status', $msg)->with('open_goals', true);
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:30',
            'target' => 'required|numeric|min:100',
            'deadline' => 'nullable|date',
            'icon' => 'required|string|max:40',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['saved' => 'nullable|numeric|min:0']);

        Goal::create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'icon' => $data['icon'],
            'target' => $data['target'],
            'saved' => $data['saved'] ?? 0,
            'deadline' => $data['deadline'] ?? null,
        ]);

        return $this->back('Goal added.');
    }

    public function update(Request $request, Goal $goal)
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $data = $request->validate($this->rules());
        $goal->update($data);

        return $this->back('Goal updated.');
    }

    // add money to a goal
    public function fund(Request $request, Goal $goal)
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $data = $request->validate(['amount' => 'required|numeric|min:1']);
        $goal->increment('saved', $data['amount']);

        return $this->back('Funds added.');
    }

    public function destroy(Request $request, Goal $goal)
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->delete();

        return $this->back('Goal deleted.');
    }
}