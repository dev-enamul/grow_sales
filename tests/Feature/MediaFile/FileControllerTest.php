<?php

namespace Tests\Feature\MediaFile;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\FileItem;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('filesystems.default', 'local');

        $category = CompanyCategory::forceCreate([
            'name' => 'Media',
            'slug' => Str::slug('Media-' . Str::random(6)),
            'description' => 'Media companies',
        ]);

        $this->company = Company::forceCreate([
            'name' => 'Pixel Corp',
            'category_id' => $category->id,
            'is_active' => true,
            'is_verified' => true,
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'media@example.com',
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_index_returns_paginated_files_with_filters(): void
    {
        $folder = Folder::create(['name' => 'Contracts']);

        FileItem::create([
            'original_name' => 'nda.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'path' => 'files/company_' . $this->company->id . '/root/nda.pdf',
            'size' => 512,
            'folder_id' => null,
            'user_id' => $this->user->id,
        ]);

        FileItem::create([
            'original_name' => 'agreement.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'disk' => 'local',
            'path' => 'files/company_' . $this->company->id . '/folder_' . $folder->id . '/agreement.docx',
            'size' => 1024,
            'folder_id' => $folder->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('files.index', ['search' => 'agreement']));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'meta' => [
                        'current_page' => 1,
                    ],
                ],
            ])
            ->assertJsonPath('data.data.0.original_name', 'agreement.docx');
    }

    public function test_store_uploads_file_and_persists_metadata(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('proposal.pdf', 256, 'application/pdf');

        $response = $this->postJson(route('files.store'), [
            'file' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.original_name', 'proposal.pdf');

        $storedPath = $response->json('data.path');

        Storage::disk('local')->assertExists($storedPath);

        $this->assertDatabaseHas('files', [
            'original_name' => 'proposal.pdf',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_show_returns_file_with_download_links(): void
    {
        $fileItem = FileItem::create([
            'original_name' => 'brand-assets.zip',
            'mime_type' => 'application/zip',
            'disk' => 'local',
            'path' => 'files/company_' . $this->company->id . '/root/brand-assets.zip',
            'size' => 2048,
            'folder_id' => null,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson(route('files.show', $fileItem->id));

        $response->assertOk()
            ->assertJsonPath('data.id', $fileItem->id)
            ->assertJsonPath('data.download_url', route('files.download', $fileItem->id));
    }

    public function test_update_moves_file_when_folder_changes(): void
    {
        Storage::fake('local');

        $initialFolder = Folder::create(['name' => 'Root Files']);
        $targetFolder = Folder::create(['name' => 'Archives']);

        $upload = UploadedFile::fake()->create('minutes.pdf', 128, 'application/pdf');

        $storeResponse = $this->postJson(route('files.store'), [
            'file' => $upload,
            'folder_id' => $initialFolder->id,
        ])->assertCreated();

        $fileId = $storeResponse->json('data.id');
        $originalPath = $storeResponse->json('data.path');

        Storage::disk('local')->assertExists($originalPath);

        $updateResponse = $this->putJson(route('files.update', $fileId), [
            'folder_id' => $targetFolder->id,
        ])->assertOk();

        $newPath = $updateResponse->json('data.path');

        Storage::disk('local')->assertMissing($originalPath);
        Storage::disk('local')->assertExists($newPath);

        $this->assertDatabaseHas('files', [
            'id' => $fileId,
            'folder_id' => $targetFolder->id,
            'path' => $newPath,
        ]);
    }

    public function test_destroy_removes_file_from_storage(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('to-delete.txt', 4, 'text/plain');

        $storeResponse = $this->postJson(route('files.store'), [
            'file' => $file,
        ])->assertCreated();

        $fileId = $storeResponse->json('data.id');
        $path = $storeResponse->json('data.path');

        Storage::disk('local')->assertExists($path);

        $this->deleteJson(route('files.destroy', $fileId))
            ->assertOk()
            ->assertJsonPath('message', 'File deleted successfully');

        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseMissing('files', ['id' => $fileId]);
    }

    public function test_store_rejects_folder_from_another_company(): void
    {
        Storage::fake('local');

        $otherCategory = CompanyCategory::forceCreate([
            'name' => 'Finance',
            'slug' => Str::slug('Finance-' . Str::random(6)),
            'description' => 'Finance companies',
        ]);

        $otherCompany = Company::forceCreate([
            'name' => 'Other Corp',
            'category_id' => $otherCategory->id,
            'is_active' => true,
            'is_verified' => true,
        ]);

        $foreignUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'finance@example.com',
        ]);

        Sanctum::actingAs($foreignUser);

        $foreignFolder = Folder::create([
            'name' => 'Foreign',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson(route('files.store'), [
            'file' => UploadedFile::fake()->create('illegal.pdf', 64, 'application/pdf'),
            'folder_id' => $foreignFolder->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.folder_id.0', 'Selected folder is invalid.');
    }
}

