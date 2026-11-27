<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use Illuminate\Http\Request;

class AcceptAnswerController extends Controller
{
    public function __invoke(Request $request, Answer $answer)
    {
        $question = $answer->question;

        abort_unless($question->user_id === $request->user()->id, 403);

        $question->accepted_answer_id =
            $question->accepted_answer_id === $answer->id ? null : $answer->id;
        $question->save();

        return back();
    }
}