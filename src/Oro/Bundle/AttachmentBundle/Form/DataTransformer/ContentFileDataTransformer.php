<?php

namespace Oro\Bundle\AttachmentBundle\Form\DataTransformer;

use Oro\Bundle\AttachmentBundle\Entity\File;

/**
 * Transforms a value between raw file content and File entity
 */
class ContentFileDataTransformer implements ContentFileDataTransformerInterface
{
    private const string DEFAULT_FILE_NAME = 'file.json';

    private ?string $savedContent = null;

    public function __construct(
        private string $fileName = self::DEFAULT_FILE_NAME
    ) {
    }

    #[\Override]
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @param null|string $value
     */
    #[\Override]
    public function transform(mixed $value): ?File
    {
        if ($value === null) {
            return null;
        }

        $file = new File();
        $file->setFilename($this->fileName);
        $file->setFileSize(mb_strlen($value));
        $file->setEmptyFile(false);

        $this->savedContent = $value;

        return $file;
    }

    /**
     * @param null|File $value
     */
    #[\Override]
    public function reverseTransform(mixed $value): ?string
    {
        if ($value === null || (!$value->getFile() && $value?->isEmptyFile())) {
            return null;
        }

        if ($value->getFile()) {
            return $value->getFile()->getContent();
        }

        return $this->savedContent;
    }
}
