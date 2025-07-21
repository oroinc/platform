<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Exception;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplate\PdfTemplateInterface;
use Throwable;

/**
 * Thrown when a PDF template renderer fails to render a template.
 */
final class PdfTemplateRenderingException extends PdfGeneratorException
{
    private ?PdfTemplateInterface $pdfTemplate;

    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        ?PdfTemplateInterface $pdfTemplate = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->pdfTemplate = $pdfTemplate;
    }

    public function getPdfTemplate(): ?PdfTemplateInterface
    {
        return $this->pdfTemplate;
    }
}
