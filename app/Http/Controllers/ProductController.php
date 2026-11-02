<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductIngredient;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:Active,Inactive'],
            'sort' => ['nullable', 'in:name,price'],
        ]);
        $productsQuery = Product::query()
            ->with('productIngredients.ingredient')
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(
                    'product_name',
                    'like',
                    '%'.$search.'%'
                )
            )
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status)
            );
        $products = ($filters['sort'] ?? 'name') === 'price'
            ? $productsQuery->orderBy('price')->orderBy('product_name')->paginate(15)->withQueryString()
            : $productsQuery->orderBy('product_name')->paginate(15)->withQueryString();

        $productIngredients = ProductIngredient::with(['product', 'ingredient'])
            ->orderBy('product_id')
            ->orderBy('ingredient_id')
            ->get();

        $activeTab = $request->query('tab') === 'recipes' ? 'recipes' : 'products';

        return view(
            'products.index',
            compact('products', 'productIngredients', 'activeTab', 'filters')
        );
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
                'unique:products,product_name',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);
        $image = $this->productImageUpload($request);

        $productData = collect($validated)->except('image')->all();
        $imagePath = null;

        if ($image) {
            $imagePath = $this->storeProductImage($image);
            $productData['image_path'] = $imagePath;
        }

        try {
            DB::transaction(function () use ($productData, $activityLogger): void {
                $product = Product::create($productData);

                $activityLogger->record(
                    action: 'product.created',
                    module: 'products',
                    description: 'Product created.',
                    subject: $product,
                    newValues: $this->auditValues($product),
                );
            });
        } catch (Throwable $exception) {
            if ($imagePath && ! Storage::disk('public')->delete($imagePath)) {
                throw new RuntimeException('The product was not created and its uploaded image could not be removed.', previous: $exception);
            }

            throw $exception;
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load([
            'productIngredients.ingredient',
            'orderItems',
        ]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product, ActivityLogger $activityLogger)
    {
        $validated = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_name')
                    ->ignore($product->product_id, 'product_id'),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                'required',
                'in:Active,Inactive',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'remove_image' => [
                'nullable',
                'boolean',
            ],
        ]);
        $image = $this->productImageUpload($request);

        $productData = collect($validated)->except(['image', 'remove_image'])->all();
        $oldImagePath = $product->image_path;
        $newImagePath = null;

        if ($image) {
            $newImagePath = $this->storeProductImage($image);
            $productData['image_path'] = $newImagePath;
        } elseif ($request->boolean('remove_image')) {
            $productData['image_path'] = null;
        }

        try {
            DB::transaction(function () use ($product, $productData, $activityLogger): void {
                $product = Product::query()
                    ->whereKey($product->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $oldValues = $this->auditValues($product);
                $product->update($productData);

                $activityLogger->record(
                    action: 'product.updated',
                    module: 'products',
                    description: 'Product information updated.',
                    subject: $product,
                    oldValues: $oldValues,
                    newValues: $this->auditValues($product),
                );
            });
        } catch (Throwable $exception) {
            if ($newImagePath && ! Storage::disk('public')->delete($newImagePath)) {
                throw new RuntimeException('The product was not updated and its uploaded image could not be removed.', previous: $exception);
            }

            throw $exception;
        }

        $product->refresh();

        if (
            $oldImagePath
            && $oldImagePath !== $product->image_path
            && ! Storage::disk('public')->delete($oldImagePath)
        ) {
            throw new RuntimeException('The product was updated but its previous image could not be removed.');
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    private function productImageUpload(Request $request): ?UploadedFile
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $image = $request->file('image');
        $pathname = $image instanceof UploadedFile
            ? $image->getPathname()
            : '';

        if (
            ! ($image instanceof UploadedFile)
            || ! $image->isValid()
            || $pathname === ''
            || ! is_file($pathname)
            || ! is_readable($pathname)
        ) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded product photo could not be read. Please choose the image again.',
            ]);
        }

        return $image;
    }

    private function storeProductImage(UploadedFile $image): string
    {
        $pathname = $image->getPathname();
        $stream = @fopen($pathname, 'r');

        if ($stream === false) {
            throw ValidationException::withMessages([
                'image' => 'The uploaded product photo could not be read. Please choose the image again.',
            ]);
        }

        $imagePath = 'products/'.$image->hashName();

        try {
            $stored = Storage::disk('public')->put($imagePath, $stream);
        } finally {
            fclose($stream);
        }

        if (! $stored) {
            throw new RuntimeException('The product image could not be saved.');
        }

        return $imagePath;
    }

    /**
     * @return array{ product_name: string, price: string, status: string, image_attached: bool }
     */
    private function auditValues(Product $product): array
    {
        return [
            'product_name' => $product->product_name,
            'price' => (string) $product->price,
            'status' => $product->status,
            'image_attached' => $product->image_path !== null,
        ];
    }

    public function destroy(Product $product, ActivityLogger $activityLogger)
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors([
                'product' => 'This product cannot be deleted because it has order records.',
            ]);
        }

        if ($product->productIngredients()->exists()) {
            return back()->withErrors([
                'product' => 'This product cannot be deleted because it has recipe ingredients. Remove the recipe ingredients first.',
            ]);
        }

        DB::transaction(function () use ($product, $activityLogger): void {
            $oldValues = $this->auditValues($product);
            $product->delete();

            $activityLogger->record(
                action: 'product.deleted',
                module: 'products',
                description: 'Product deleted.',
                subject: $product,
                oldValues: $oldValues,
            );
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
