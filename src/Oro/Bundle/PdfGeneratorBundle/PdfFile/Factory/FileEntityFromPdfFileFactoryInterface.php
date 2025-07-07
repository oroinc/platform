<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfFile\Factory;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;

/**
 * Interface for a factory that creates a File entity from a PDF file.
 */
interface FileEntityFromPdfFileFactoryInterface
{
    /**
     * Creates a File entity from a PDF file.
     *
     * @param PdfFileInterface $pdfFile The PDF file to create the File entity from.
     * @param string $filename The name of the file.
     *
     * @return File The created File entity.
     */
    public function createFile(PdfFileInterface $pdfFile, string $filename): File;
}
