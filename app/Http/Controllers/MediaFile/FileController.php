<?php

namespace App\Http\Controllers\MediaFile;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaFile\StoreFileRequest;
use App\Http\Requests\MediaFile\UpdateFileRequest;
use App\Models\FileItem;
use App\Models\Folder;
use App\Traits\PaginatorTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        $query = $this->applyFileFilters(
            $this->fileQuery()->with('folder'),
            $request
        )->latest();

        $files = $this->paginateQuery($query, $request);

        return success_response($files);
    }

    public function store(StoreFileRequest $request)
    {
        [$uploadedFile, $folderId] = $this->validateUpload($request);

        $user = Auth::user();
        $companyId = $user?->company_id ?? null;
        $disk = config('filesystems.default', 'local');
        $directory = $this->resolveDirectory($folderId, $companyId);
        $storedPath = $uploadedFile->store($directory, $disk);

        // Use custom file_name if provided, otherwise use original file name
        $customFileName = $request->input('file_name');
        if ($customFileName) {
            // If custom name doesn't have extension, add original file's extension
            $originalExtension = $uploadedFile->getClientOriginalExtension();
            $originalName = $customFileName;
            if ($originalExtension && !str_contains($customFileName, '.')) {
                $originalName = $customFileName . '.' . $originalExtension;
            }
        } else {
            $originalName = $uploadedFile->getClientOriginalName();
        }

        $fileItem = FileItem::create([
            'original_name' => $originalName,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'disk' => $disk,
            'path' => $storedPath,
            'size' => $uploadedFile->getSize(),
            'folder_id' => $folderId,
            'user_id' => $user?->id,
        ])->load('folder');

        return success_response($fileItem, 'File uploaded successfully', 201);
    }

    public function show($id)
    {
        $file = $this->findFileOrFail($id, ['folder']);

        $file->setAttribute('download_url', route('files.download', $file->id));
        $file->setAttribute('temporary_url', $this->makeTemporaryUrl($file));

        return success_response($file);
    }

    public function download($id, Request $request)
    {
        $file = $this->findFileOrFail($id);

        // If force_download parameter is set, always download
        $forceDownload = $request->boolean('force_download');

        // If it's an image and not forcing download, serve inline for preview
        if (!$forceDownload && str_starts_with($file->mime_type, 'image/')) {
            return Storage::disk($file->disk)->response($file->path, $file->original_name, [
                'Content-Type' => $file->mime_type,
                'Content-Disposition' => 'inline; filename="' . $file->original_name . '"',
            ]);
        }

        // Otherwise, download the file
        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function update(UpdateFileRequest $request, $id)
    {
        $file = $this->findFileOrFail($id);

        $data = $request->validated();

        $changes = $this->prepareUpdateChanges($file, $data);

        if (! empty($changes)) {
            $file->update($changes);
        }

        $file->load('folder');

        return success_response($file->refresh(), 'File updated successfully');
    }

    public function destroy($id)
    {
        $file = $this->findFileOrFail($id);

        Storage::disk($file->disk)->delete($file->path);
        $file->delete();

        return success_response(null, 'File deleted successfully');
    }

    protected function applyFileFilters(Builder $query, Request $request): Builder
    {
        $query = $query
            ->when($request->has('folder_id'), function (Builder $builder) use ($request) {
                $folderId = $request->input('folder_id');
                if ($folderId === null || $folderId === 'null' || $folderId === '') {
                    // Show only root level files (folder_id IS NULL)
                    $builder->whereNull('folder_id');
                } else {
                    // Show files in specific folder
                    $builder->where('folder_id', $request->integer('folder_id'));
                }
            })
            ->when($request->filled('search'), function (Builder $builder) use ($request) {
                $search = $request->input('search');
                $builder->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('original_name', 'like', "%{$search}%")
                        ->orWhere('mime_type', 'like', "%{$search}%");
                });
            });

        if ($request->filled('type')) {
            $type = trim(strtolower($request->input('type')));
            $query->where(function (Builder $builder) use ($type) {
                $mimeGroups = [
                    'images' => ['image/'],
                    'documents' => ['application/pdf', 'text/', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    'audio' => ['audio/'],
                    'video' => ['video/'],
                    'archives' => ['application/zip', 'application/x-7z-compressed', 'application/x-rar-compressed', 'application/x-tar'],
                ];

                if (isset($mimeGroups[$type])) {
                    $builder->where(function (Builder $inner) use ($mimeGroups, $type) {
                        foreach ($mimeGroups[$type] as $mime) {
                            $inner->orWhere('mime_type', 'like', "{$mime}%");
                        }
                    });
                } elseif ($type === 'other') {
                    $builder->whereNot(function (Builder $inner) use ($mimeGroups) {
                        foreach ($mimeGroups as $group) {
                            foreach ($group as $mime) {
                                $inner->orWhere('mime_type', 'like', "{$mime}%");
                            }
                        }
                    });
                }
            });
        }

        if ($request->filled('sort_by')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

            $sortable = [
                'name' => 'original_name',
                'size' => 'size',
                'date' => 'updated_at',
                'type' => 'mime_type',
            ];

            if (isset($sortable[$sortBy])) {
                $query->orderBy($sortable[$sortBy], $sortDirection);
            }
        }

        return $query;
    }

    protected function validateUpload(StoreFileRequest $request): array
    {
        $data = $request->validated();

        $folderId = $data['folder_id'] ?? null;

        $this->assertFolderBelongsToCompany($folderId);

        return [$request->file('file'), $folderId];
    }

    protected function prepareUpdateChanges(FileItem $file, array $data): array
    {
        $changes = [];

        if (array_key_exists('original_name', $data)) {
            $changes['original_name'] = $data['original_name'];
        }

        if (array_key_exists('folder_id', $data) && $data['folder_id'] !== $file->folder_id) {
            $this->assertFolderBelongsToCompany($data['folder_id']);

            $user = Auth::user();
            $companyId = $user?->company_id ?? $file->company_id;
            $disk = $file->disk;
            $fileName = Str::afterLast($file->path, '/');
            $newDirectory = $this->resolveDirectory($data['folder_id'], $companyId);
            $newPath = "{$newDirectory}/{$fileName}";

            Storage::disk($disk)->makeDirectory($newDirectory);

            if (! Storage::disk($disk)->move($file->path, $newPath)) {
                throw ValidationException::withMessages([
                    'folder_id' => ['Failed to move file to the new folder.'],
                ]);
            }

            $changes['folder_id'] = $data['folder_id'];
            $changes['path'] = $newPath;
        }

        return $changes;
    }

    protected function resolveDirectory(?int $folderId, ?int $companyId): string
    {
        $segments = ['files'];

        if ($companyId) {
            $segments[] = 'company_' . $companyId;
        }

        $segments[] = $folderId ? 'folder_' . $folderId : 'root';

        return implode('/', $segments);
    }

    protected function makeTemporaryUrl(FileItem $file, int $minutes = 5): ?string
    {
        $disk = Storage::disk($file->disk);

        if (! $disk->has($file->path)) {
            return null;
        }

        if (method_exists($disk, 'temporaryUrl')) {
            try {
                return $disk->temporaryUrl(
                    $file->path,
                    now()->addMinutes($minutes),
                    ['ResponseContentDisposition' => 'attachment; filename="' . $file->original_name . '"']
                );
            } catch (\Throwable $exception) {
                // Ignore and fall back to direct download route.
            }
        }

        return route('files.download', $file->id);
    }

    protected function fileQuery(): Builder
    {
        $query = FileItem::query();

        if ($companyId = Auth::user()?->company_id) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    protected function folderQuery(): Builder
    {
        $query = Folder::query();

        if ($companyId = Auth::user()?->company_id) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    protected function findFileOrFail($id, array $relations = []): FileItem
    {
        return $this->fileQuery()
            ->with($relations)
            ->findOrFail($id);
    }

    protected function assertFolderBelongsToCompany(?int $folderId): void
    {
        if ($folderId === null) {
            return;
        }

        if (! $this->folderQuery()->whereKey($folderId)->exists()) {
            throw ValidationException::withMessages([
                'folder_id' => ['Selected folder is invalid.'],
            ]);
        }
    }
}
