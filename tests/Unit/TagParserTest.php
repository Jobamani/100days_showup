<?php

namespace Tests\Unit;

use App\Support\TagParser;
use PHPUnit\Framework\TestCase;

class TagParserTest extends TestCase
{
    public function test_it_splits_trims_and_drops_empty_entries(): void
    {
        $this->assertSame(['laravel', 'php'], TagParser::parse(' laravel ,, php ,'));
    }

    public function test_it_collapses_internal_whitespace(): void
    {
        $this->assertSame(['query builder'], TagParser::parse('query    builder'));
    }

    public function test_it_removes_duplicates_ignoring_case(): void
    {
        $this->assertSame(['Laravel'], TagParser::parse('Laravel, laravel, LARAVEL'));
    }

    public function test_null_and_empty_input_give_no_tags(): void
    {
        $this->assertSame([], TagParser::parse(null));
        $this->assertSame([], TagParser::parse('  ,  '));
    }
}