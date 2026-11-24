<?php

namespace App\Http\Controllers;

use App\Models\Question;

class QuestionController extends Controller
{
    public function show(Question $question, ?string $slug = null)
    {
        // '' (title with no sluggable characters) and a missing slug must count as equal,
        // otherwise this redirects to itself forever
        if (($slug ?? '') !== $question->slug) {
            return redirect()->route('questions.show', [$question, $question->slug], 301);
        }

        $question->load([
            'user',
            'tags',
            'answers' => fn ($query) => $query->oldest()->orderBy('id'),
            'answers.user',
        ]);

        return view('questions.show', compact('question'));
    }
}