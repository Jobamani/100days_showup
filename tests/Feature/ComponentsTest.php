<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;

class ComponentsTest extends TestCase
{
    public function test_card_renders_slot_and_merges_classes(): void
    {
        $this->blade('<x-card class="mt-4">Hello</x-card>')
            ->assertSee('Hello')
            ->assertSee('mt-4', false);
    }

    public function test_primary_button_renders_label_and_defaults_to_submit(): void
    {
        $this->blade('<x-primary-button>Save</x-primary-button>')
            ->assertSee('Save')
            ->assertSee('type="submit"', false);
    }

    public function test_primary_button_type_can_be_overridden(): void
    {
        $this->blade('<x-primary-button type="button">Cancel</x-primary-button>')
            ->assertSee('type="button"', false);
    }

    public function test_tag_pill_shows_tag_name(): void
    {
        $tag = (new Tag)->forceFill(['name' => 'Laravel']);

        $this->blade('<x-tag-pill :tag="$tag" />', ['tag' => $tag])
            ->assertSee('Laravel');
    }
}