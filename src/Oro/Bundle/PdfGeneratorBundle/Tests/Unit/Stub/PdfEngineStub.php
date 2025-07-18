<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\Stub;

use Oro\Bundle\PdfGeneratorBundle\PdfEngine\PdfEngineInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;

class PdfEngineStub implements PdfEngineInterface
{
    public function __construct(private PdfFileInterface $pdfFile)
    {
    }

    public static function getName(): string
    {
        return 'pdf_engine_stub';
    }

    public function createPdfFile(PdfOptionsInterface $pdfOptions): PdfFileInterface
    {
        return $this->pdfFile;
    }
}
