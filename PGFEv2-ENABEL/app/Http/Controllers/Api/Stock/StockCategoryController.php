<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class StockCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = \App\Models\StockCategory::where('school_id', $user->school_id);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        }
        $categories = $query->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'message' => 'Liste des catégories récupérée avec succès.',
            'data' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = \App\Models\StockCategory::create([
            ...$validated,
            'school_id' => $user->school_id,
            'user_id' => $user->id,
        ]);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $category = \App\Models\StockCategory::where('school_id', $user->school_id)->findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $category = \App\Models\StockCategory::where('school_id', $user->school_id)->findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);
        $category->update($validated);
        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $category = \App\Models\StockCategory::where('school_id', $user->school_id)->findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'deleted']);
    }
}
