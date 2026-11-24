<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreAnswerRequest;
use App\Models\Question;
use App\Models\Answer;

class AnswerController extends Controller
{
    public function store(StoreAnswerRequest $request, Question $question)
    {
        // question_id comes from the route, user_id from the session, never from input
        $answer = $question->answers()->make($request->validated());
        $answer->user()->associate($request->user());
        $answer->save();

        return redirect()
            ->route('questions.show', [$question, $question->slug])
            ->with('status', 'Your answer was posted.');
    }
}
