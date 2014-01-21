<?php

namespace Oro\Bundle\ImportExportBundle\MimeType;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser;

class MimeTypeGuesser extends MimeTypeExtensionGuesser
{
    /**
     * Guesses a mime type for the given file extension
     *
     * @param string $extension A file extension
     * @return string|null The guessed mime type or NULL, if none could be guessed
     */
    public function guessByFileExtension($extension)
    {
        $extension = strtolower($extension);
        foreach ($this->defaultExtensions as $mimeType => $ext) {
            if ($extension === $ext) {
                return $mimeType;
            }
        }

        return null;
    }
}
