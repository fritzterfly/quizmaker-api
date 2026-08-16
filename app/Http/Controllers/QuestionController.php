<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    protected function ensureQuizOwnership(Quiz $quiz): void
    {
        if ($quiz->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function ensureQuestionBelongsToQuiz(Quiz $quiz, Question $question): void
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404, 'Question not found in this quiz.');
        }
    }

    public function index(Quiz $quiz)
    {
        $this->ensureQuizOwnership($quiz);

        $questions = $quiz->questions()
            ->with('choices')
            ->get();

        return response()->json($questions);
    }

    public function store(Request $request, Quiz $quiz)
    {
        $this->ensureQuizOwnership($quiz);

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
        ]);

        $question = $quiz->questions()->create([
            'question_text' => $validated['question_text'],
        ]);

        return response()->json($question->load('choices'), 201);
    }

    public function show(Quiz $quiz, Question $question)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        $question->load('choices');

        return response()->json($question);
    }

    public function update(Request $request, Quiz $quiz, Question $question)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        $validated = $request->validate([
            'question_text' => ['sometimes', 'required', 'string'],
        ]);

        $question->update($validated);

        return response()->json($question->fresh()->load('choices'));
    }

    public function destroy(Quiz $quiz, Question $question)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully.',
        ]);
    }
}
