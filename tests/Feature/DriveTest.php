<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_view_drive(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->get(route('drive.index'))
            ->assertOk();
    }

    public function test_user_can_create_folder_and_upload_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->post(route('drive.folders.store'), [
                'name' => 'Projects',
            ])
            ->assertRedirect();

        $folder = Folder::query()->where('name', 'Projects')->firstOrFail();

        $this->actingAs($user)
            ->post(route('drive.files.store'), [
                'folder_id' => $folder->id,
                'file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('files', [
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'name' => 'notes.txt',
        ]);
    }

    public function test_user_can_share_folder_with_another_user(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('user');

        $recipient = User::factory()->create([
            'email' => 'share-target@example.com',
        ]);
        $recipient->assignRole('user');

        $folder = Folder::query()->create([
            'user_id' => $owner->id,
            'name' => 'Shared Docs',
        ]);

        $this->actingAs($owner)
            ->post(route('drive.folders.share', $folder), [
                'email' => $recipient->email,
                'permission' => 'view',
            ])
            ->assertRedirect();

        $this->actingAs($recipient)
            ->get(route('drive.index', ['folder' => $folder->id]))
            ->assertOk();
    }
}
