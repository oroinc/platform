<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfFile\Factory;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;

/**
 * Creates a File entity from a PDF file.
 */
class FileEntityFromPdfFileFactory implements FileEntityFromPdfFileFactoryInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {
    }

    #[\Override]
    public function createFile(PdfFileInterface $pdfFile, string $filename): File
    {
        $tempFile = $this->fileManager->writeToTemporaryFile(
            $pdfFile->getStream()->getContents()
        );

        $file = new File();
        $file->setFile($tempFile);
        $file->setOriginalFilename($filename);
        $file->setMimeType($pdfFile->getMimeType());

        return $file;
    }
}
