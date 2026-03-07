<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use App\Services\ImageKitService;

class CategoryImage extends Model
{
    protected $fillable = [
        'category_id',
        'image_url',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Model Methods
    |--------------------------------------------------------------------------
    */

    public static function saveImage(int $categoryId, UploadedFile $file): self
    {
        $url = (new ImageKitService())->upload($file, 'categories');

        return self::create([
            'category_id' => $categoryId,
            'image_url'   => $url,
        ]);
    }

    public static function getImage(int $categoryId): ?string
    {
        $categoryImage = self::where('category_id', $categoryId)->first();
        return $categoryImage ? $categoryImage->image_url : null;
    }

    public function updateImage(UploadedFile $file): bool
    {
        $imageKit = new ImageKitService();

        if ($this->image_url) {
            $imageKit->delete($this->image_url);
        }

        $this->image_url = $imageKit->upload($file, 'categories');
        return $this->save();
    }

    public function deleteImage(): bool
    {
        if ($this->image_url) {
            (new ImageKitService())->delete($this->image_url);
        }

        return (bool) $this->delete();
    }
}