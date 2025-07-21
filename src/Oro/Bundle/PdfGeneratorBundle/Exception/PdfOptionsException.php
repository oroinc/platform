<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Exception;

use Oro\Bundle\PdfGeneratorBundle\PdfOptions\PdfOptionsInterface;

/**
 * Thrown when PDF options failed to resolve.
 */
final class PdfOptionsException extends PdfGeneratorException
{
    private ?PdfOptionsInterface $pdfOptions;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?PdfOptionsInterface $pdfOptions = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->pdfOptions = $pdfOptions;
    }

    public function getPdfOptions(): ?PdfOptionsInterface
    {
        return $this->pdfOptions;
    }
}
