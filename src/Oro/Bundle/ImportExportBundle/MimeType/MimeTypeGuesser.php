<?php

namespace Oro\Bundle\ImportExportBundle\MimeType;

use Symfony\Component\Mime\MimeTypes;

class MimeTypeGuesser
{
    /**
     * Guesses a mime type for the given file extension
     *
     * @param string $extension A file extension
     * @return string|null The guessed mime type or NULL, if none could be guessed
     */
    public function guessByFileExtension($extension)
    {
        return MimeTypes::getDefault()->guessMimeType($extension);
    }
}
