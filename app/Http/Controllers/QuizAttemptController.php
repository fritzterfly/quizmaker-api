<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
