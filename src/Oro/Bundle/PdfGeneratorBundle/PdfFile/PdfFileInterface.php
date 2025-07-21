<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfFile;

use Psr\Http\Message\StreamInterface;

/**
 * Represents a generated PDF file.
 */
interface PdfFileInterface
{
    /**
     * @return StreamInterface PDF file contents represented as {@see StreamInterface}.
     */
    public function getStream(): StreamInterface;

    /**
     * @return string Path to the PDF file in filesystem.
     */
    public function getPath(): string;

    public function getMimeType(): string;
}
