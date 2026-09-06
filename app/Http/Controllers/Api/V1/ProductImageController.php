<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UploadProductImageRequest;
use App\Models\Product;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Resources\ProductResource;

class ProductImageController extends Controller
{
    public function store(UploadProductImageRequest $request, string $id)
    {
        $product = Product::find($id);

        if(!$product) {
            return ApiResponse::error(
                'Product not found',
                Response::HTTP_NOT_FOUND
            );
        }

        if($product->image){
            Storage::disk('public')->delete($product->image);
        }

        $path = $request->file('image')->store('products', 'public');

        $product->update(['image' => $path]);

        return ApiResponse::success(
            new ProductResource($product->load('category')),
            'Product image uploaded'
        );
    }
}
