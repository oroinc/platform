<?php

namespace Oro\Bundle\EmailBundle\EmbeddedImages;

/**
 * DTO that holds embedded images data extracted from content.
 */
class EmbeddedImage
{
    private string $encodedContent;

    private ?string $filename;

    private ?string $contentType;

    private ?string $encoding;

    public function __construct(
        string $encodedContent,
        string $filename = null,
        string $contentType = null,
        string $encoding = null
    ) {
        $this->encodedContent = $encodedContent;
        $this->filename = $filename;
        $this->contentType = $contentType;
        $this->encoding = $encoding;
    }

    public function getEncodedContent(): string
    {
        return $this->encodedContent;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }
}
