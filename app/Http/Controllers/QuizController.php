<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Auth::user()
            ->quizzes()
            ->with(['questions' => function ($query) {
                $query->with('choices');
            }])
            ->latest()
            ->get();

        return response()->json($quizzes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $quiz = Auth::user()->quizzes()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json($quiz, 201);
    }

    public function show(Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $quiz->load(['questions' => function ($query) {
            $query->with('choices');
        }]);

        return response()->json($quiz);
    }

    public function update(Request $request, Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $quiz->update($validated);

        return response()->json($quiz->fresh());
    }

    public function destroy(Quiz $quiz)
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $quiz->delete();

        return response()->json([
            'message' => 'Quiz deleted successfully.',
        ]);
    }
}
