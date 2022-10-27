<?php

namespace Oro\Bundle\AttachmentBundle\Guesser;

use Oro\Bundle\ImportExportBundle\MimeType\UploadedFileExtensionHelper;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * MIME Type guesser for ms outlook msg files.
 */
class MsMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * @var array
     */
    protected $typesMap = [
        'msg' => [
            'd0cf11e0a1b11ae1' => 'application/vnd.ms-outlook',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function guessMimeType(string $path): ?string
    {
        $extension = UploadedFileExtensionHelper::getExtensionByPath($path);
        if (!$extension) {
            return null;
        }

        if (!isset($this->typesMap[$extension])) {
            return null;
        }

        $handle = fopen($path, 'r');
        $bytes = bin2hex(fread($handle, 8));
        fclose($handle);

        if (!isset($this->typesMap[$extension][$bytes])) {
            return null;
        }

        return $this->typesMap[$extension][$bytes];
    }

    /**
     * {@inheritdoc}
     */
    public function isGuesserSupported(): bool
    {
        return true;
    }
}
