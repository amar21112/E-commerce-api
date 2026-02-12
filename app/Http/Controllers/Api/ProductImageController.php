<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductImageController extends Controller
{
    protected $imageService;

    public function __construct(ProductImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload single image for a product
     */
    public function upload(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product=Product::findOrFail($request->product_id);
        try {
            DB::beginTransaction();

            $productImage = $this->imageService->storeImage(
                $product,
                $request->file('image')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => $productImage
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload multiple images for a product
     */
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = Product::findOrFail($request->product_id);
        try {
            DB::beginTransaction();

            $uploadedImages = $this->imageService->storeMultipleImages(
                $product,
                $request->file('images')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($uploadedImages) . ' images uploaded successfully',
                'data' => $uploadedImages
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product image
     */
    public function destroy(ProductImage $productImage)
    {
        try {
            DB::beginTransaction();

            $this->imageService->deleteImage($productImage);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a product image
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_image_id' => 'required|exists:product_images,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $productImage = ProductImage::findOrFail($request->product_image_id);

        try {
            DB::beginTransaction();

            $updatedImage = $this->imageService->updateImage(
                $productImage,
                $request->file('image')
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Image updated successfully',
                'data' => $updatedImage
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update image: ' . $e->getMessage()
            ], 500);
        }
    }
}
