<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageService
{
    /**
     * Store a single product image
     *
     * @param Product $product
     * @param UploadedFile $image
     * @param string $directory
     * @return ProductImage
     */
    public function storeImage(Product $product, UploadedFile $image, $directory = 'images')
    {
        // Generate a unique filename
        $filename = Str::random(40) . '.' . $image->getClientOriginalExtension();

        // Store the image
        $path = $image->storeAs(
            "products/{$product->id}/{$directory}",
            $filename,
            'product_images'
        );

        // Generate URL
        $url = Storage::disk('product_images')->url($path);

        // Create database record
        $productImage = $product->images()->create([
            'url' => $url,
            'path' => $path
        ]);

        return $productImage;
    }

    /**
     * Store multiple product images
     *
     * @param Product $product
     * @param array $images
     * @param string $directory
     * @return array
     */
    public function storeMultipleImages(Product $product, array $images, $directory = 'images')
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            $uploadedImages[] = $this->storeImage($product, $image, $directory);
        }

        return $uploadedImages;
    }

    /**
     * Delete a product image and its file
     *
     * @param ProductImage $productImage
     * @return bool
     */
    public function deleteImage(ProductImage $productImage)
    {
        // Delete the physical file
        if ($productImage->path && Storage::disk('product_images')->exists($productImage->path)) {
            Storage::disk('product_images')->delete($productImage->path);

            // Try to delete empty directory
            $directory = dirname($productImage->path);
            if (Storage::disk('product_images')->files($directory) === 0) {
                Storage::disk('product_images')->deleteDirectory($directory);
            }
        }

        // Delete database record
        return $productImage->delete();
    }

    /**
     * Delete all images for a product
     *
     * @param Product $product
     * @return bool
     */
    public function deleteAllProductImages(Product $product)
    {
        // Delete all physical files
        $directory = "products/{$product->id}";
        if (Storage::disk('product_images')->exists($directory)) {
            Storage::disk('product_images')->deleteDirectory($directory);
        }

        // Delete all database records
        return $product->images()->delete();
    }

    /**
     * Update product image
     *
     * @param ProductImage $productImage
     * @param UploadedFile $newImage
     * @return ProductImage
     */
    public function updateImage(ProductImage $productImage, UploadedFile $newImage)
    {
        // Store new image
        $product = $productImage->product;
        $directory = dirname($productImage->path);

        $filename = Str::random(40) . '.' . $newImage->getClientOriginalExtension();
        $path = $newImage->storeAs($directory, $filename, 'product_images');
        $url = Storage::disk('product_images')->url($path);

        // Delete old image file
        if ($productImage->path && Storage::disk('product_images')->exists($productImage->path)) {
            Storage::disk('product_images')->delete($productImage->path);
        }

        // Update database record
        $productImage->update([
            'url' => $url,
            'path' => $path
        ]);

        return $productImage;
    }

}
