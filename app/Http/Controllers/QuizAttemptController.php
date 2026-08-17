<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizAttemptController extends Controller
{
    public function store(Request $request, Quiz $quiz)
    {
        if (! $quiz->is_published) {
            return response()->json([
                'message' => 'This quiz is not published.',
            ], 403);
        }

        $questionCount = $quiz->questions()->count();

        if ($questionCount === 0) {
            return response()->json([
                'message' => 'This quiz has no questions.',
            ], 422);
        }

        $attempt = Auth::user()->quizAttempts()->create([
            'quiz_id' => $quiz->id,
            'total_questions' => $questionCount,
            'started_at' => now(),
            'score' => null,
            'completed_at' => null,
        ]);

        return response()->json($attempt, 201);
    }

    public function submit(QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized attempt access.',
            ], 403);
        }

        if ($attempt->completed_at !== null) {
            return response()->json([
                'message' => 'This attempt has already been submitted.',
            ], 409);
        }

        $quiz = $attempt->quiz()->with('questions')->first();
        $totalQuestions = $quiz->questions()->count();

        if ($totalQuestions === 0) {
            return response()->json([
                'message' => 'This quiz has no questions.',
            ], 422);
        }

        $answeredQuestionIds = $attempt->answers()->pluck('question_id')->all();
        $expectedQuestionIds = $quiz->questions()->pluck('id')->all();
        $missingQuestionIds = array_diff($expectedQuestionIds, $answeredQuestionIds);

        if (! empty($missingQuestionIds)) {
            return response()->json([
                'message' => 'All questions must be answered before submission.',
            ], 422);
        }

        $score = (int) $attempt->answers()->where('is_correct', true)->count();
        $percentage = $totalQuestions > 0 ? ($score / $totalQuestions) * 100 : 0;

        DB::transaction(function () use ($attempt, $score, $totalQuestions, $percentage) {
            $attempt->update([
                'score' => $score,
                'total_questions' => $totalQuestions,
                'completed_at' => now(),
            ]);
        });

        return response()->json([
            'id' => $attempt->id,
            'quiz_id' => $attempt->quiz_id,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'percentage' => $percentage,
            'started_at' => $attempt->started_at,
            'completed_at' => $attempt->fresh()->completed_at,
        ]);
    }
}
