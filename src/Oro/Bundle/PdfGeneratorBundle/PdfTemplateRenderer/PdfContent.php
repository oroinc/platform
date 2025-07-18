<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer;

use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAssetInterface;

/**
 * Represents a PDF content.
 */
class PdfContent implements PdfContentInterface
{
    /**
     * @param string $content
     * @param array<string,PdfTemplateAssetInterface> $assets
     */
    public function __construct(private string $content, private array $assets = [])
    {
    }

    #[\Override]
    public function getContent(): string
    {
        return $this->content;
    }

    #[\Override]
    public function getAssets(): array
    {
        return $this->assets;
    }
}
