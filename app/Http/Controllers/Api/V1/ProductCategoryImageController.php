<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UploadProductCategoryImageRequest;
use App\Models\ProductCategory;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\ProductCategoryResource;

class ProductCategoryImageController extends Controller
{
    public function store(UploadProductCategoryImageRequest $request, string $id)
    {
        $category = ProductCategory::find($id);

        if(!$category) {
            return ApiResponse::error(
                'Product category not found',
                Response::HTTP_NOT_FOUND
            );
        }

        if($category->image){
            Storage::disk('public')->delete($category->image);
        }

        $path = $request->file('image')->store('product_categories', 'public');

        $category->update(['image' => $path]);

        return ApiResponse::success(
            new ProductCategoryResource($category),
            'Product category image uploaded'
        );
    }
}
