<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Markdown
{
    public static function render(?string $text): HtmlString
    {
        return new HtmlString(Str::markdown((string) $text, [
            'html_input' => 'escape',       // raw HTML is shown as text, never rendered
            'allow_unsafe_links' => false,  // blocks javascript:, data: and vbscript: links
            'max_nesting_level' => 20,      // deeply nested input can't exhaust memory
        ]));
    }
}