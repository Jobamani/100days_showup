<?php

namespace Tests\Unit;

use App\Support\Markdown;
use PHPUnit\Framework\TestCase;

class MarkdownTest extends TestCase
{
    public function test_it_renders_basic_markdown(): void
    {
        $html = (string) Markdown::render("**bold** and `code`\n\n- one\n- two");

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<code>code</code>', $html);
        $this->assertStringContainsString('<li>one</li>', $html);
    }

    public function test_raw_html_is_escaped_not_rendered(): void
    {
        $html = (string) Markdown::render('<script>alert(1)</script> <img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_javascript_links_are_neutralised(): void
    {
        $html = (string) Markdown::render('[click me](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('click me', $html);
    }

    public function test_normal_links_still_work(): void
    {
        $html = (string) Markdown::render('[Laravel](https://laravel.com)');

        $this->assertStringContainsString('href="https://laravel.com"', $html);
    }
}