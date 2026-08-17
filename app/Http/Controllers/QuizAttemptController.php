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

    public function index()
    {
        $attempts = Auth::user()
            ->quizAttempts()
            ->with('quiz:id,title')
            ->latest()
            ->get()
            ->map(function ($attempt) {
                $percentage = $attempt->total_questions > 0
                    ? ($attempt->score / $attempt->total_questions) * 100
                    : 0;

                return [
                    'id' => $attempt->id,
                    'quiz_id' => $attempt->quiz_id,
                    'quiz_title' => $attempt->quiz?->title,
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'percentage' => $percentage,
                    'started_at' => $attempt->started_at,
                    'completed_at' => $attempt->completed_at,
                    'status' => $attempt->completed_at ? 'completed' : 'in_progress',
                ];
            });

        return response()->json($attempts);
    }

    public function result(QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized attempt access.',
            ], 403);
        }

        if ($attempt->completed_at === null) {
            return response()->json([
                'message' => 'This attempt is still in progress.',
            ], 422);
        }

        $attempt->load([
            'quiz:id,title,description',
            'answers' => function ($query) {
                $query->with(['question:id,question_text', 'choice:id,choice_text']);
            },
        ]);

        $answers = $attempt->answers->map(function ($answer) {
            $correctChoice = $answer->question?->choices()->where('is_correct', true)->first();

            return [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question?->question_text,
                'selected_choice' => $answer->choice ? [
                    'id' => $answer->choice->id,
                    'choice_text' => $answer->choice->choice_text,
                ] : null,
                'is_correct' => (bool) $answer->is_correct,
                'correct_choice' => $correctChoice ? [
                    'id' => $correctChoice->id,
                    'choice_text' => $correctChoice->choice_text,
                ] : null,
            ];
        });

        $percentage = $attempt->total_questions > 0
            ? ($attempt->score / $attempt->total_questions) * 100
            : 0;

        return response()->json([
            'id' => $attempt->id,
            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
                'description' => $attempt->quiz->description,
            ],
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
            'percentage' => $percentage,
            'started_at' => $attempt->started_at,
            'completed_at' => $attempt->completed_at,
            'answers' => $answers,
        ]);
    }
}
