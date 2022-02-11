<?php

namespace Oro\Bundle\AttachmentBundle\Mapper;

/**
 * Allows map client mimeType to well-known system mimeType.
 */
class ClientMimeTypeMapper implements ClientMimeTypeMapperInterface
{
    private array $map = [
        'application/x-zip-compressed' => 'application/zip'
    ];

    public function addMapping(string $originalMimeType, string $returnedMimeType): void
    {
        $this->map[$originalMimeType] = $returnedMimeType;
    }

    public function getMimeType(string $originalMimeType): string
    {
        return $this->map[$originalMimeType] ?? $originalMimeType;
    }
}
