<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('parent')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(),
            'parentOptions' => $this->parentOptions(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($this->payloadFrom($request));

        HomeController::clearCache();
        flash_message('Category created successfully!');

        return redirect()->route('categories.index');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($category),
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($this->payloadFrom($request, $category));

        HomeController::clearCache();
        flash_message('Category updated successfully!');

        return redirect()->route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->deletePublicFile($category->icon);
        $this->deletePublicFile($category->image);
        $this->deletePublicFile($category->meta_image);

        $category->delete();

        HomeController::clearCache();
        flash_message('Category deleted successfully!');

        return redirect()->route('categories.index');
    }

    protected function payloadFrom(CategoryRequest $request, ?Category $category = null): array
    {
        $data = $request->validated();

        $data['parent_id'] = $data['parent_id'] ?? null;
        if ($category && $data['parent_id'] === $category->id) {
            $data['parent_id'] = null;
        }
        $data['slug'] = Str::slug($data['slug'] ?? $data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_popular'] = $request->boolean('is_popular');
        $data['icon_class'] = $data['icon_class'] ?? null;
        $data['meta_title'] = $data['meta_title'] ?? null;
        $data['meta_description'] = $data['meta_description'] ?? null;
        $data['meta_keywords'] = $data['meta_keywords'] ?? null;

        if ($request->hasFile('icon')) {
            $this->deletePublicFile($category?->icon);
            $data['icon'] = upload_webp_image($request->file('icon'), 'uploads/categories/icons', 75);
        } else {
            unset($data['icon']);
        }

        if ($request->hasFile('image')) {
            $oldImage = $category?->image;
            $data['image'] = upload_webp_image($request->file('image'), 'uploads/categories', 80);

            // Keep meta image in sync when it was empty or reused the category image.
            if (! $request->hasFile('meta_image') && (! $category?->meta_image || $category->meta_image === $oldImage)) {
                $data['meta_image'] = $data['image'];
            }

            $metaStillUsesOld = $oldImage
                && $category?->meta_image === $oldImage
                && ! isset($data['meta_image']);

            if ($oldImage && ! $metaStillUsesOld) {
                $this->deletePublicFile($oldImage);
            }
        } else {
            unset($data['image']);
        }

        if ($request->hasFile('meta_image')) {
            $oldMetaImage = $category?->meta_image;
            $data['meta_image'] = upload_webp_image($request->file('meta_image'), 'uploads/categories/meta', 80);

            if ($oldMetaImage && $oldMetaImage !== $category?->image) {
                $this->deletePublicFile($oldMetaImage);
            }
        } elseif (! isset($data['meta_image'])) {
            unset($data['meta_image']);
        }

        return $data;
    }

    protected function deletePublicFile(?string $path): void
    {
        delete_uploaded_file($path);
    }

    protected function parentOptions(?Category $ignore = null): Collection
    {
        $categories = Category::select(['id', 'name', 'parent_id'])
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        $options = collect();

        $buildTree = function ($parentId = null, $prefix = '') use (
            &$buildTree,
            &$options,
            $categories,
            $ignore
        ): void {
            $categories
                ->where('parent_id', $parentId)
                ->each(function (Category $category) use ($prefix, &$buildTree, &$options, $ignore): void {
                    if ($ignore && $category->id === $ignore->id) {
                        return;
                    }

                    $options->push([
                        'id' => $category->id,
                        'label' => $prefix . $category->name,
                    ]);

                    $buildTree(
                        parentId: $category->id,
                        prefix: $prefix . '-- '
                    );
                });
        };

        $buildTree();

        return $options;
    }
}
