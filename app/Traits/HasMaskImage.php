<?php

namespace App\Traits;

use App\Http\Controllers\FileController;

trait HasMaskImage
{

    private function generateMaskedImageAppUrl($path): string
    {
        $filePath = FileController::encryptDecrypt($path) . '_masked.png';
        try {
            return route('file.getFile', ['type' => 'image', 'path' => $filePath]);
        }
        catch (\Exception $exception) {
            return config('app.url') . '/file/image/' . $filePath;
        }
    }

}
