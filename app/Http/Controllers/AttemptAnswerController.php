<?php

namespace App\Http\Controllers;

use App\Models\AttemptAnswer;
use App\Models\Choice;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttemptAnswerController extends Controller
{
    public function store(Request $request, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized attempt access.',
            ], 403);
        }

        if ($attempt->completed_at !== null) {
            return response()->json([
                'message' => 'This attempt is already completed.',
            ], 409);
        }

        $validated = $request->validate([
            'question_id' => ['required', 'integer'],
            'choice_id' => ['required', 'integer'],
        ]);

        $question = $attempt->quiz->questions()->find($validated['question_id']);

        if (! $question) {
            return response()->json([
                'message' => 'Invalid question for this quiz.',
            ], 422);
        }

        $choice = $question->choices()->find($validated['choice_id']);

        if (! $choice) {
            return response()->json([
                'message' => 'Invalid choice for this question.',
            ], 422);
        }

        $exists = $attempt->answers()
            ->where('question_id', $question->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A response for this question has already been submitted.',
            ], 409);
        }

        $answer = $attempt->answers()->create([
            'question_id' => $question->id,
            'choice_id' => $choice->id,
            'is_correct' => (bool) $choice->is_correct,
        ]);

        return response()->json([
            'id' => $answer->id,
            'quiz_attempt_id' => $answer->quiz_attempt_id,
            'question_id' => $answer->question_id,
            'choice_id' => $answer->choice_id,
            'created_at' => $answer->created_at,
        ], 201);
    }
}
