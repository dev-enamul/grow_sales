<?php 
namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductCategoryRequest;
use App\Models\CategoryType;
use App\Models\MeasurmentUnit;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use App\Services\ProductCategoryService;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{ 
    use PaginatorTrait; 
    public function index(Request $request)
    {
        $keyword = $request->keyword;
        $progressStage = $request->progress_stage; 
        $areaId = $request->area_id;
        $selectOnly = $request->boolean('select');

        $query = ProductCategory::where('company_id', Auth::user()->company_id)
            ->where('applies_to','property')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('slug', 'like', '%' . $keyword . '%')
                        ->orWhere('address', 'like', '%' . $keyword . '%');
                });
            })
            ->when($progressStage, function ($query) use ($progressStage) {
                $query->where('progress_stage', $progressStage);
            }) 
            ->when($areaId, function ($query) use ($areaId) {
                $query->where('area_id', $areaId);
            })
            ->select('id','uuid', 'name', 'progress_stage','description','address', 'ready_date', 'status', 'area_id','category_type_id','measurment_unit_id')
            ->with(['area:id,name']);
        
        if ($selectOnly) {
            $units = $query->select('id','name')->latest()->take(10)->get();
            return success_response($units);
        }

        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSorts = ['name','ready_date']; 
        if ($sortBy && in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();  
        }

        $paginated = $this->paginateQuery($query, $request);
 
        $paginated['data'] = collect($paginated['data'])->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'name' => $item->name,
                'progress_stage' => $item->progress_stage,
                'ready_date' => formatDate($item->ready_date),
                'status' => $item->status,
                'area_name' => optional($item->area)->name,
                'category_type_id' => $item->category_type_id,
                'measurment_unit_id' => $item->measurment_unit_id,
                'address' => $item->address,
                'description' => $item->description,
                'area_id' => $item->area_id,
            ];
        });

        return success_response($paginated);
    } 

    public function show($uuid)
    {
        $category = ProductCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->with([
                'categoryType:id,name',
                'measurmentUnit:id,name,abbreviation',
                'area:id,name',
                'creator:id,name',
                'updater:id,name',
                'deleter:id,name'
            ])
            ->first();

        if (!$category) {
            return error_response('Product category not found', 404);
        }

        return success_response([
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'progress_stage' => $category->progress_stage,
            'ready_date' => formatDate($category->ready_date),
            'address' => $category->address,
            'status' => $category->status,
            'category_type' => optional($category->categoryType)->name,
            'measurment_unit' => optional($category->measurmentUnit)->name,
            'measurment_abbreviation' => optional($category->measurmentUnit)->abbreviation,
            'area_name' => optional($category->area)->name,
            'created_by' => optional($category->creator)->name,
            'updated_by' => optional($category->updater)->name,
            'deleted_by' => optional($category->deleter)->name,
            'created_at' => formatDate($category->created_at),
            'updated_at' => formatDate($category->updated_at),
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255', 
            'description' => 'nullable|string',
            'progress_stage' => 'required|in:Ready,Ongoing,Upcomming,Complete',
            'ready_date' => 'nullable|date',
            'address' => 'nullable|string',
            'category_type_id' => 'nullable|exists:category_types,id',
            'measurment_unit_id' => 'nullable|exists:measurment_units,id',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $category = new ProductCategory();
        $category->company_id = Auth::user()->company_id;
        $category->name = $request->name;
        $category->slug = getSlug($category,$request->name);
        $category->description = $request->description;
        $category->progress_stage = $request->progress_stage;
        $category->ready_date = $request->ready_date;
        $category->address = $request->address;
        $category->status = 1;
        $category->applies_to = 'property';

        // ✅ Default category_type_id যদি null হয়
        $category_type_id = $request->category_type_id;
        if ($category_type_id === null) {
            $activeCategoryTypes = CategoryType::where('is_active', true)->get();
            if ($activeCategoryTypes->count() === 1) {
                $category_type_id = $activeCategoryTypes->first()->id;
            }
        }
        $category->category_type_id = $category_type_id;

        // ✅ Default measurment_unit_id যদি null হয়
        $measurment_unit_id = $request->measurment_unit_id;
        if ($measurment_unit_id === null) {
            $activeUnits = MeasurmentUnit::where('is_active', true)->get();
            if ($activeUnits->count() === 1) {
                $measurment_unit_id = $activeUnits->first()->id;
            }
        }
        $category->measurment_unit_id = $measurment_unit_id;
        

        $category->area_id = $request->area_id;
        $category->created_by = Auth::id();
        $category->save();

        return success_response(null, 'Product category created successfully!');
    }


    public function update(Request $request, $uuid)
    {
        $request->validate([
            'name' => 'required|string|max:255', 
            'description' => 'nullable|string',
            'progress_stage' => 'required|in:Ready,Ongoing,Upcomming,Complete',
            'ready_date' => 'nullable|date',
            'address' => 'nullable|string',
            'status' => 'nullable|in:0,1',
            'category_type_id' => 'nullable|exists:category_types,id',
            'measurment_unit_id' => 'nullable|exists:measurment_units,id',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $category = ProductCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$category) {
            return error_response('Product category not found', 404);
        }

        $category->name = $request->name;
        $category->slug = getSlug(new ProductCategory(),$request->name);
        $category->description = $request->description;
        $category->progress_stage = $request->progress_stage;
        $category->ready_date = $request->ready_date;
        $category->address = $request->address;
        $category->status = $request->status ?? $category->status;

        // ✅ category_type_id ফিল্ডে default logic
        $category_type_id = $request->category_type_id;
        if ($category_type_id === null) {
            $activeCategoryTypes = CategoryType::where('is_active', true)->get();
            if ($activeCategoryTypes->count() === 1) {
                $category_type_id = $activeCategoryTypes->first()->id;
            }
        }
        $category->category_type_id = $category_type_id;

        // ✅ measurment_unit_id ফিল্ডে default logic
        $measurment_unit_id = $request->measurment_unit_id;
        if ($measurment_unit_id === null) {
            $activeUnits = MeasurmentUnit::where('is_active', true)->get();
            if ($activeUnits->count() === 1) {
                $measurment_unit_id = $activeUnits->first()->id;
            }
        }
        $category->measurment_unit_id = $measurment_unit_id;

        $category->area_id = $request->area_id;
        $category->updated_by = Auth::id();
        $category->save();

        return success_response(null, 'Product category updated successfully!');
    }


    public function destroy($uuid)
    {
        $category = ProductCategory::where('uuid', $uuid)
            ->where('company_id', Auth::user()->company_id)
            ->first();

        if (!$category) {
            return error_response('Product category not found', 404);
        }

        $subCategoryCount = ProductSubCategory::where('category_id', $category->id)->count();

        if ($subCategoryCount > 0) {
            return error_response("You can't delete this category because it has sub-categories.", 400);
        }

        $category->deleted_by = Auth::id();
        $category->save();
        $category->delete();

        return success_response(null, 'Product category deleted successfully.');
    }

}
