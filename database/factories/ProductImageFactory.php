<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = ProductImage::class;

    public function definition()
    {
        // Image URL
        $imageUrl = 'https://source.unsplash.com/random/1500x1500/';
        // Nonaktif SSL
        $context = stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ]);

        // Download Image
        $imageData = file_get_contents($imageUrl, false,$context);

        // Convert Image to .webp
        $ext = 'webp';
        $imageConvert = Image::make($imageData)->encode($ext, 100);

        // Generate Unique File Name
        $fileName = uniqid() . '.' . $ext;

        // Calculate the compression level needed to reduce the file size to 200 KB
        $compressionLevel = (200000 * 100) / strlen($imageConvert);

        // Limit the compression level to 100 (maximum)
        if ($compressionLevel > 100) {
            $compressionLevel = 100;
        }

        // Resize image to reduce file size and save Image to Storage
        $targetPath = storage_path('app/public/assets/images/product_images/');

        if (!File::isDirectory($targetPath)) {
            File::makeDirectory($targetPath, 0777, true, true);
        }

        $imageConvert->save($targetPath . $fileName, $compressionLevel);

        return [
            'image_name' => 'assets/images/product_images/' . $fileName,
            'product_id' => function () {
                return Product::factory()->create()->product_id;
            }
        ];
    }
}
