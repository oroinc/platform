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

    #[\Override]
    public function addMapping(string $originalMimeType, string $returnedMimeType): void
    {
        $this->map[$originalMimeType] = $returnedMimeType;
    }

    #[\Override]
    public function getMimeType(string $originalMimeType): string
    {
        return $this->map[$originalMimeType] ?? $originalMimeType;
    }
}
