<?php

namespace App\Http\Controllers\MediaFile;

use App\Http\Controllers\Controller;
use App\Http\Requests\MediaFile\StoreFolderRequest;
use App\Http\Requests\MediaFile\UpdateFolderRequest;
use App\Models\Folder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class FolderController extends Controller
{
    public function index()
    {
        $folders = $this->folderQuery()
            ->whereNull('parent_id')
            ->withCount(['children', 'files'])
            ->orderByDesc('id')
            ->get();

        return success_response($folders);
    }

    public function store(StoreFolderRequest $request)
    {
        $data = $this->ensureValidParent($request->validated());

        $folder = Folder::create([
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $folder->loadMissing(['children:id,parent_id', 'files:id,folder_id']);

        return success_response($folder, 'Folder created successfully', 201);
    }

    public function show($id)
    {
        $folder = $this->findFolderOrFail($id, ['children', 'files']);

        return success_response($folder);
    }

    public function update(UpdateFolderRequest $request, $id)
    {
        $folder = $this->findFolderOrFail($id, ['children:id,parent_id']);

        $data = $this->ensureValidParent($request->validated(), $folder);

        $folder->update($data);

        $folder->loadMissing(['children', 'files']);

        return success_response($folder->refresh(), 'Folder updated successfully');
    }

    public function destroy($id)
    {
        $folder = $this->findFolderOrFail($id)->loadCount(['children', 'files']);

        if ($folder->children_count > 0 || $folder->files_count > 0) {
            return error_response('Cannot delete folder with children or files. Delete them first.', 422);
        }

        $folder->delete();

        return success_response(null, 'Folder deleted successfully');
    }

    protected function ensureValidParent(array $data, ?Folder $folder = null): array
    {
        if (! array_key_exists('parent_id', $data)) {
            return $data;
        }

        if ($data['parent_id'] === null) {
            return $data;
        }

        $parent = $this->findFolderOrFail($data['parent_id']);

        if ($folder && $parent->id === $folder->id) {
            throw ValidationException::withMessages([
                'parent_id' => ['Folder cannot be parent of itself.'],
            ]);
        }

        if ($folder && $folder->relationLoaded('children') && $folder->children->contains('id', $parent->id)) {
            throw ValidationException::withMessages([
                'parent_id' => ['Cannot move folder under its own child.'],
            ]);
        }

        return $data;
    }

    protected function folderQuery(): Builder
    {
        $query = Folder::query();

        if ($companyId = Auth::user()?->company_id) {
            $query->where('company_id', $companyId);
        }

        return $query;
    }

    protected function findFolderOrFail($id, array $relations = []): Folder
    {
        return $this->folderQuery()
            ->with($relations)
            ->findOrFail($id);
    }
}
