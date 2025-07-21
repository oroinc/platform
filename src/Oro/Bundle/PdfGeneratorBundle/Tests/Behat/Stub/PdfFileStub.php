<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Behat\Stub;

use GuzzleHttp\Psr7\Utils;
use Oro\Bundle\PdfGeneratorBundle\PdfFile\PdfFileInterface;
use Psr\Http\Message\StreamInterface;

class PdfFileStub implements PdfFileInterface
{
    public function __construct(private string $path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("PDF file stub does not exist: $path");
        }
    }

    public function getStream(): StreamInterface
    {
        return Utils::streamFor(file_get_contents($this->path));
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->path;
    }

    #[\Override]
    public function getMimeType(): string
    {
        return 'application/pdf';
    }
}
