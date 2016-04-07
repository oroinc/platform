<?php

namespace Oro\Bundle\AttachmentBundle\Guesser;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

use Oro\Component\PhpUtils\ArrayUtil;

class MsMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /** @var array */
    protected $typesMap = [
        'd0cf11e0a1b11ae1' => [
            'msg' => 'application/vnd.ms-outlook',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function guess($filePath)
    {
        $file = ArrayUtil::find(
            function (array $file) use ($filePath) {
                return $file['tmp_name']['file'] === $filePath;
            },
            $_FILES
        );

        if (!$file) {
            return null;
        }

        $fileName = $file['name']['file'];
        $pos = strrpos($fileName, '.');
        if (!$pos) {
            return null;
        }

        $extension = substr($fileName, $pos + 1);

        $handle = fopen($filePath, 'r');
        $bytes = bin2hex(fread($handle, 8));
        fclose($handle);

        if (!isset($this->typesMap[$bytes][$extension])) {
            return null;
        }

        return $this->typesMap[$bytes][$extension];
    }
}
