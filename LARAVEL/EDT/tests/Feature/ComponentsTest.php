<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestView;
use Tests\TestCase;

class ComponentsTest extends TestCase
{
    use RefreshDatabase;

    private function formPage(bool $blocked): TestView
    {
        $this->actingAs(User::factory()->admin()->create());

        return $this->blade(<<<'BLADE'
<x-form-page title="Ajouter un truc" action="/truc" method="put" submit="Créer le truc" :blocked="$blocked" x-data="{ ok: true }">
    <x-slot name="blockedMessage">Rien à faire <a href="/c">créer</a></x-slot>
    <p>CHAMPS</p>
    <x-slot name="after"><p>APRES</p></x-slot>
</x-form-page>
BLADE, ['blocked' => $blocked]);
    }

    public function test_form_page_renders_the_form_with_its_fields_method_and_extra_content(): void
    {
        $this->formPage(false)
            ->assertSee('Ajouter un truc')
            ->assertSee('<form method="POST" action="/truc"', false)
            ->assertSee('name="_method" value="put"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('CHAMPS')
            ->assertSee('Créer le truc')
            ->assertSee('APRES');
    }

    public function test_form_page_can_replace_the_form_with_a_blocking_message(): void
    {
        $this->formPage(true)
            ->assertSee('Rien à faire')
            ->assertSee('<a href="/c">créer</a>', false)
            ->assertSee('APRES')
            ->assertDontSee('<form method="POST" action="/truc"', false)
            ->assertDontSee('CHAMPS')
            ->assertDontSee('Créer le truc');
    }

    public function test_form_page_passes_extra_attributes_to_the_form(): void
    {
        $this->formPage(false)
            ->assertSee('x-data="{ ok: true }"', false)
            ->assertSee('space-y-4', false);
    }

    public function test_select_component_renders_the_standard_field_classes(): void
    {
        $this->blade('<x-select id="type" name="type" required><option value="a">A</option></x-select>')
            ->assertSee('<select', false)
            ->assertSee('id="type"', false)
            ->assertSee('name="type"', false)
            ->assertSee('required', false)
            ->assertSee('mt-1 block w-full', false)
            ->assertSee('rounded-md shadow-sm', false)
            ->assertSee('<option value="a">A</option>', false);
    }

    public function test_compact_select_has_no_layout_classes(): void
    {
        $this->blade('<x-select compact aria-label="Licence"><option>A</option></x-select>')
            ->assertSee('aria-label="Licence"', false)
            ->assertSee('text-sm', false)
            ->assertDontSee('mt-1 block w-full', false)
            ->assertDontSee('w-full', false);
    }

    public function test_select_component_passes_alpine_attributes_through(): void
    {
        $this->blade('<x-select x-ref="level" @change="selected = $event.target.value"><option>A</option></x-select>')
            ->assertSee('x-ref="level"', false)
            ->assertSee('@change="selected = $event.target.value"', false);
    }

    public function test_confirm_button_spoofs_the_method_and_marks_dangerous_actions(): void
    {
        $this->blade('<x-confirm-button action="/groupes/1" confirm="Supprimer ?" method="delete" danger>Supprimer</x-confirm-button>')
            ->assertSee('method="POST"', false)
            ->assertSee('action="/groupes/1"', false)
            ->assertSee('name="_method" value="delete"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('text-red-600', false)
            ->assertDontSee('text-gray-500', false)
            ->assertSee('>Supprimer</button>', false);
    }

    public function test_confirm_button_defaults_to_a_plain_post(): void
    {
        $this->blade('<x-confirm-button action="/reset" confirm="Réinitialiser ?">Réinitialiser</x-confirm-button>')
            ->assertSee('action="/reset"', false)
            ->assertDontSee('_method', false)
            ->assertSee('text-gray-500', false)
            ->assertDontSee('text-red-600', false);
    }

    public function test_confirm_message_is_escaped_for_javascript_and_html(): void
    {
        $this->blade('<x-confirm-button action="/x" :confirm="$message">Ok</x-confirm-button>', [
            'message' => "Supprimer l'élément \"test\" </script> ?",
        ])
            ->assertSee('onsubmit="return confirm(', false)
            ->assertSee('l\\u0027', false)
            ->assertSee('\\u0022test\\u0022', false)
            ->assertDontSee('</script>', false);
    }

    public function test_delete_lesson_form_posts_to_the_right_route_with_a_single_confirm_wording(): void
    {
        $lesson = Lesson::factory()->create();

        $this->blade('<x-delete-lesson-form :lesson="$lesson" class="absolute top-1 right-1"><button type="submit">×</button></x-delete-lesson-form>', [
            'lesson' => $lesson,
        ])
            ->assertSee('action="'.route('lessons.destroy', $lesson).'"', false)
            ->assertSee('name="_method" value="delete"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('onsubmit="return confirm(\'Supprimer ce cours ?\')"', false)
            ->assertSee('class="absolute top-1 right-1"', false)
            ->assertSee('<button type="submit">×</button>', false);
    }
}
