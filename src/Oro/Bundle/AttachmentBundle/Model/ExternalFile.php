<?php

namespace Oro\Bundle\AttachmentBundle\Model;

/**
 * Represents an externally stored file.
 */
class ExternalFile extends \SplFileInfo
{
    private string $url;

    private string $originalName;

    private int $size;

    private string $mimeType;

    public function __construct(
        string $url,
        string $originalName = '',
        int $size = 0,
        string $mimeType = ''
    ) {
        parent::__construct(parse_url($url, \PHP_URL_PATH));

        $this->url = $url;
        $this->originalName = $originalName;
        $this->size = $size;
        $this->mimeType = $mimeType;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getOriginalExtension(): string
    {
        return pathinfo($this->originalName, \PATHINFO_EXTENSION);
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
