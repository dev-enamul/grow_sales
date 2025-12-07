<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Traits\PaginatorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChallengeController extends Controller
{
    use PaginatorTrait;

    public function index(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $selectOnly = $request->boolean('select');
            $query = Challenge::where('company_id', Auth::user()->company_id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword . '%')
                        ->orWhere('slug', 'like', '%' . $keyword . '%');
                });

            if ($selectOnly) {
                $challenges = $query->select('id', 'title')->latest()->take(10)->get();
                return success_response($challenges);
            }

            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            $allowedSorts = ['title', 'slug'];
            if ($sortBy && in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->latest();
            }

            $challenges = $this->paginateQuery($query->select('uuid', 'title', 'slug', 'status'), $request);

            return success_response($challenges);
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'nullable|integer',
        ]);

        try {
            $challenge = new Challenge();
            $challenge->title = $request->input('title');
            $challenge->slug = getSlug(new Challenge(), $request->input('title'));
            $challenge->status = $request->status;
            $challenge->save();

            return success_response(null, 'Lead Qualification Challenge created successfully!', 201);
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'status' => 'nullable|integer',
        ]);

        try {
            $challenge = Challenge::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$challenge) {
                return error_response('Lead Qualification Challenge not found', 404);
            }

            $challenge->title = $request->input('title');
            $challenge->slug = getSlug($challenge, $request->input('title'));
            $challenge->status = $request->status ?? $challenge->status;
            $challenge->save();

            return success_response(null, 'Lead Qualification Challenge updated successfully!');
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }

    public function destroy($uuid)
    {
        try {
            $challenge = Challenge::where('uuid', $uuid)
                ->where('company_id', Auth::user()->company_id)
                ->first();

            if (!$challenge) {
                return error_response('Lead Qualification Challenge not found', 404);
            }

            $challenge->delete();
            return success_response(null, 'Lead Qualification Challenge deleted successfully.');
        } catch (\Exception $e) {
            return error_response($e->getMessage());
        }
    }
}

