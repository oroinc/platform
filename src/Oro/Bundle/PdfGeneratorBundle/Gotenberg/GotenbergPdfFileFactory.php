<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Gotenberg;

use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFile;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates an instance of {@see PdfFile} from the response received from Gotenberg API.
 */
class GotenbergPdfFileFactory
{
    public function createPdfFile(ResponseInterface $response): PdfFileInterface
    {
        return new PdfFile($response->getBody(), $this->getMimeType($response));
    }

    private function getMimeType(ResponseInterface $httpResponse): ?string
    {
        $contentType = $httpResponse->getHeader('Content-Type');

        return reset($contentType) ?: 'application/pdf';
    }
}
