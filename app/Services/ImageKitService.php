<?php

namespace App\Services;

use ImageKit\ImageKit;
use App\Models\ImageKitFileId;

class ImageKitService
{
    private ImageKit $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.url_endpoint'),
        );
    }

    /**
     * Upload a file to ImageKit and store the fileId against the URL.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  string  $folder   e.g. 'products', 'categories'
     * @return string            The ImageKit URL of the uploaded file
     *
     * @throws \RuntimeException
     */
    public function upload($file, string $folder): string
    {
        $fileName = $this->generateUniqueFileName($file->getClientOriginalExtension());

        $response = $this->imageKit->uploadFile([
            'file'              => base64_encode(file_get_contents($file->getRealPath())),
            'fileName'          => $fileName,
            'folder'            => $folder,
            'useUniqueFileName' => false,
        ]);

        if ($response->error) {
            throw new \RuntimeException('ImageKit upload failed: ' . json_encode($response->error));
        }

        $url    = $response->result->url;
        $fileId = $response->result->fileId;

        ImageKitFileId::create([
            'url'     => $url,
            'file_id' => $fileId,
        ]);

        return $url;
    }

    /**
     * Delete a file from ImageKit by its URL.
     * Looks up the fileId from the database, deletes from ImageKit,
     * then removes the record.
     *
     * @param  string  $url
     * @return void
     *
     * @throws \RuntimeException
     */
    public function delete(string $url): void
    {
        $fileId = ImageKitFileId::getFileIdByUrl($url);

        if (!$fileId) {
            throw new \RuntimeException("No ImageKit fileId found for URL: {$url}");
        }

        $this->imageKit->deleteFile($fileId);

        ImageKitFileId::where('url', $url)->delete();
    }

    /**
     * Generate a unique 7-digit numeric filename with the original extension.
     * e.g. "4829301.jpg"
     */
    private function generateUniqueFileName(string $extension): string
    {
        $digits = random_int(1_000_000, 9_999_999);
        return $extension ? "{$digits}.{$extension}" : (string) $digits;
    }
}