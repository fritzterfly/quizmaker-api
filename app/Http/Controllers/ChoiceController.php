<?php

namespace App\Http\Controllers;

use App\Models\Choice;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChoiceController extends Controller
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

    protected function ensureChoiceBelongsToQuestion(Question $question, Choice $choice): void
    {
        if ($choice->question_id !== $question->id) {
            abort(404, 'Choice not found in this question.');
        }
    }

    public function index(Quiz $quiz, Question $question)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        $choices = $question->choices()->get();

        return response()->json($choices);
    }

    public function store(Request $request, Quiz $quiz, Question $question)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);

        $validated = $request->validate([
            'choice_text' => ['required', 'string', 'max:255'],
            'is_correct' => ['sometimes', 'boolean'],
        ]);

        $choice = $question->choices()->create([
            'choice_text' => $validated['choice_text'],
            'is_correct' => $validated['is_correct'] ?? false,
        ]);

        return response()->json($choice, 201);
    }

    public function show(Quiz $quiz, Question $question, Choice $choice)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);
        $this->ensureChoiceBelongsToQuestion($question, $choice);

        return response()->json($choice);
    }

    public function update(Request $request, Quiz $quiz, Question $question, Choice $choice)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);
        $this->ensureChoiceBelongsToQuestion($question, $choice);

        $validated = $request->validate([
            'choice_text' => ['sometimes', 'required', 'string', 'max:255'],
            'is_correct' => ['sometimes', 'boolean'],
        ]);

        $choice->update($validated);

        return response()->json($choice->fresh());
    }

    public function destroy(Quiz $quiz, Question $question, Choice $choice)
    {
        $this->ensureQuizOwnership($quiz);
        $this->ensureQuestionBelongsToQuiz($quiz, $question);
        $this->ensureChoiceBelongsToQuestion($question, $choice);

        $choice->delete();

        return response()->json([
            'message' => 'Choice deleted successfully.',
        ]);
    }
}
