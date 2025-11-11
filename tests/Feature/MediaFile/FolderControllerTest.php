<?php

namespace Tests\Feature\MediaFile;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\FileItem;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FolderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CompanyCategory::forceCreate([
            'name' => 'Technology',
            'slug' => Str::slug('Technology-' . Str::random(6)),
            'description' => 'Technology companies',
        ]);

        $this->company = Company::forceCreate([
            'name' => 'Acme Inc',
            'category_id' => $category->id,
            'is_active' => true,
            'is_verified' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'owner@example.com',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_index_returns_root_folders_with_related_counts(): void
    {
        $root = Folder::create(['name' => 'Root']);
        Folder::create([
            'name' => 'Child',
            'parent_id' => $root->id,
        ]);

        FileItem::create([
            'original_name' => 'contract.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'path' => 'files/company_' . $this->company->id . '/root/contract.pdf',
            'size' => 512,
            'folder_id' => $root->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('folder.index'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.0.children_count', 1)
            ->assertJsonPath('data.0.files_count', 1);
    }

    public function test_store_creates_folder(): void
    {
        $payload = [
            'name' => 'Projects',
        ];

        $response = $this->postJson(route('folder.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Projects');

        $this->assertDatabaseHas('folders', [
            'name' => 'Projects',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_update_prevents_setting_parent_to_self(): void
    {
        $folder = Folder::create(['name' => 'Operations']);

        $response = $this->putJson(route('folder.update', $folder->id), [
            'parent_id' => $folder->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.parent_id.0', 'Folder cannot be parent of itself.');
    }

    public function test_destroy_blocks_folder_with_children_or_files(): void
    {
        $folder = Folder::create(['name' => 'Assets']);
        Folder::create([
            'name' => 'Logos',
            'parent_id' => $folder->id,
        ]);

        $response = $this->deleteJson(route('folder.destroy', $folder->id));

        $response->assertStatus(422)
            ->assertJsonPath('errors', 'Cannot delete folder with children or files. Delete them first.');

        $this->assertDatabaseHas('folders', ['id' => $folder->id]);
    }

    public function test_destroy_deletes_empty_folder(): void
    {
        $folder = Folder::create(['name' => 'Transient']);

        $response = $this->deleteJson(route('folder.destroy', $folder->id));

        $response->assertOk()
            ->assertJsonPath('message', 'Folder deleted successfully');

        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }
}

