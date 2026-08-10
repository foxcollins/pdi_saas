<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_tenant_puede_subir_imagenes_a_media_local(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Media Demo', 'media-demo');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $response = $this->postJson('/app/media', [
            'file' => UploadedFile::fake()->image('hero.png', 1200, 800),
            'alt' => 'Imagen principal',
        ]);

        $response->assertOk()
            ->assertJsonPath('media.alt', 'Imagen principal');
        $this->assertStringStartsWith('/storage/', $response->json('media.url'));
        $media = Media::firstOrFail();

        $this->assertSame($tenant->id, $media->tenant_id);
        Storage::disk('public')->assertExists($media->file_key);
    }

    public function test_media_rechaza_archivos_no_imagen(): void
    {
        $user = User::factory()->create();
        $tenant = $this->makeTenant('Media Seguro', 'media-seguro');
        $tenant->users()->attach($user->id, ['role' => 'owner']);
        $this->switchTenant($tenant);
        $this->actingAs($user)->withSession(['current_tenant_id' => $tenant->id]);

        $this->postJson('/app/media', [
            'file' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }
}
