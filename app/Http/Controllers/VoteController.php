<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoteRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function question(StoreVoteRequest $request, Question $question)
    {
        $this->cast($request, $question);

        return redirect()->route('questions.show', [$question, $question->slug]);
    }

    public function answer(StoreVoteRequest $request, Answer $answer)
    {
        $this->cast($request, $answer);

        $question = $answer->question;

        return redirect()->route('questions.show', [$question, $question->slug]);
    }

    private function cast(StoreVoteRequest $request, Question|Answer $votable): void
    {
        abort_if($votable->user_id === $request->user()->id, 403, 'You cannot vote on your own post.');

        $value = (int) $request->validated('value');

        try {
            DB::transaction(function () use ($request, $votable, $value) {
                $vote = $votable->votes()->where('user_id', $request->user()->id)->lockForUpdate()->first();

                if (! $vote) {
                    $vote = $votable->votes()->make(['value' => $value]);
                    $vote->user()->associate($request->user());
                    $vote->save();
                } elseif ($vote->value === $value) {
                    $vote->delete();                 // same direction again: retract
                } else {
                    $vote->update(['value' => $value]); // other direction: switch
                }
            });
        } catch (UniqueConstraintViolationException) {
            // a double-click raced us and the vote exists, which is what the user wanted
        }
    }
}