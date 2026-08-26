<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class StockArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = \App\Models\StockArticle::where('school_id', $user->school_id)
            ->with(['category', 'provider']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhereHas('category', fn($sq) => $sq->where('name', 'like', "%$search%"))
                  ->orWhereHas('provider', fn($sq) => $sq->where('name', 'like', "%$search%"));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'low_stock') {
                $query->whereColumn('quantity', '<', 'min_threshold');
            }
        }

        $articles = $query->orderBy('name')->get();

        $data = $articles->map(function ($article) {
            $articleId = $article->id;
            $sorties = \App\Models\StockOperation::where('article_id', $articleId)
                ->where('type', 'sortie')
                ->sum('quantite');

            return array_merge($article->toArray(), [
                'stock_remaining' => ($article->quantity ?? 0) - $sorties,
                'state' => ($lastState = \App\Models\StockState::where('article_id', $articleId)
                    ->orderByDesc('state_date')
                    ->first()) ? [
                    'quantity' => $lastState->quantity,
                    'state_date' => $lastState->state_date,
                    'note' => $lastState->note,
                ] : null,
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Liste des articles récupérée avec succès.',
            'data' => $data,
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
            'category_id' => 'required|exists:stock_categories,id',
            'provider_id' => 'required|exists:stock_providers,id',
            'min_threshold' => 'required|integer|min:0',
            'max_threshold' => 'nullable|integer|min:0',
        ]);

        $article = \App\Models\StockArticle::create([
            ...$validated,
            'quantity' => 0, // Stock initial à zéro
            'school_id' => $user->school_id,
            'user_id' => $user->id,
        ]);

        // Création automatique d'une opération de stock (entrée)
        \App\Models\StockOperation::create([
            'reference' => 'ENTR-' . uniqid(),
            'type' => 'entrée',
            'article_id' => $article->id,
            'quantite' => 0, // Quantité initiale
            'operateur_id' => $user->id,
            'school_id' => $user->school_id,
        ]);

        return response()->json($article, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $article = \App\Models\StockArticle::where('school_id', $user->school_id)->findOrFail($id);
        return response()->json($article);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $article = \App\Models\StockArticle::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:stock_categories,id',
            'provider_id' => 'sometimes|exists:stock_providers,id',
            'min_threshold' => 'sometimes|integer|min:0',
            'max_threshold' => 'sometimes|integer|min:0',
        ]);
        $article->update($validated);
        return response()->json($article);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $article = \App\Models\StockArticle::findOrFail($id);
        $user = request()->user();
        // Création automatique d'une opération de stock (sortie)
        \App\Models\StockOperation::create([
            'reference' => 'SORT-' . uniqid(),
            'type' => 'sortie',
            'article_id' => $article->id,
            'quantite' => $article->quantity,
            'operateur_id' => $user->id,
            'school_id' => $user->school_id,
        ]);
        $article->delete();
        return response()->json(['message' => 'deleted']);
    }
}
