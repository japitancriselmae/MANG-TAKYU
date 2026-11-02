<?php

namespace App\Http\Controllers;

use App\Models\InventoryCategory;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $categories = InventoryCategory::query()
            ->withCount('ingredients')
            ->when(
                $validated['search'] ?? null,
                fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%')
            )
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('inventory-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('inventory-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:inventory_categories,name'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $category = DB::transaction(function () use ($validated, $activityLogger): InventoryCategory {
            $category = InventoryCategory::create($validated);

            $activityLogger->record(
                action: 'inventory_category.created',
                module: 'inventory',
                description: 'Inventory category created.',
                subject: $category,
                newValues: ['name' => $category->name],
            );

            return $category;
        });

        return redirect()
            ->route('inventory-categories.show', $category)
            ->with('success', 'Inventory category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InventoryCategory $inventoryCategory): View
    {
        $ingredients = $inventoryCategory->ingredients()
            ->with('inventories')
            ->orderBy('ingredient_name')
            ->paginate(12);

        return view(
            'inventory-categories.show',
            compact('inventoryCategory', 'ingredients')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InventoryCategory $inventoryCategory): View
    {
        return view('inventory-categories.edit', compact('inventoryCategory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        InventoryCategory $inventoryCategory,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inventory_categories', 'name')->ignore($inventoryCategory->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $inventoryCategory, $activityLogger): void {
            $inventoryCategory = InventoryCategory::query()
                ->whereKey($inventoryCategory->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $oldValues = ['name' => $inventoryCategory->name];
            $inventoryCategory->update($validated);

            $activityLogger->record(
                action: 'inventory_category.updated',
                module: 'inventory',
                description: 'Inventory category updated.',
                subject: $inventoryCategory,
                oldValues: $oldValues,
                newValues: ['name' => $inventoryCategory->name],
            );
        });

        return redirect()
            ->route('inventory-categories.show', $inventoryCategory)
            ->with('success', 'Inventory category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        InventoryCategory $inventoryCategory,
        ActivityLogger $activityLogger,
    ): RedirectResponse {
        if ($inventoryCategory->ingredients()->exists()) {
            return back()->withErrors([
                'category' => 'This category cannot be deleted while it contains stock items.',
            ]);
        }

        DB::transaction(function () use ($inventoryCategory, $activityLogger): void {
            $oldValues = ['name' => $inventoryCategory->name];
            $inventoryCategory->delete();

            $activityLogger->record(
                action: 'inventory_category.deleted',
                module: 'inventory',
                description: 'Inventory category deleted.',
                subject: $inventoryCategory,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('inventory-categories.index')
            ->with('success', 'Inventory category deleted successfully.');
    }
}
