<?php

namespace Oro\Bundle\ImportExportBundle\MimeType;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Get file extension for a given path for uploaded file.
 */
class UploadedFileExtensionHelper
{
    /**
     * @param string $path
     * @return string|null
     */
    public static function getExtensionByPath($path): ?string
    {
        $extension = null;

        $uploadedFiles = self::fetchUploadedFiles();
        if (isset($uploadedFiles[$path])) {
            $path = $uploadedFiles[$path];
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension) {
            $extension = strtolower($extension);
        }

        return $extension;
    }

    private static function fetchUploadedFiles(): array
    {
        $fileNameMap = [];
        $fileBagArray = (new FileBag($_FILES))->getIterator()->getArrayCopy();
        array_walk_recursive(
            $fileBagArray,
            static function ($item) use (&$fileNameMap) {
                if ($item instanceof UploadedFile) {
                    $fileNameMap[$item->getRealPath()] = $item->getClientOriginalName();
                }
            }
        );

        return $fileNameMap;
    }
}
