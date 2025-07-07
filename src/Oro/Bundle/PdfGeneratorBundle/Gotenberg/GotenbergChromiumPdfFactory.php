<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Gotenberg;

use Gotenberg\Gotenberg;
use Gotenberg\Modules\ChromiumPdf;
use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;

/**
 * Creates an instance of {@see ChromiumPdf}.
 */
class GotenbergChromiumPdfFactory
{
    public function createGotenbergChromiumPdf(PdfOptionsInterface $pdfOptions): ChromiumPdf
    {
        return Gotenberg::chromium($pdfOptions['gotenberg_api_url'])->pdf();
    }
}
