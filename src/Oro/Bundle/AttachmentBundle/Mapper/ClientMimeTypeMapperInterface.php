<?php

namespace Oro\Bundle\AttachmentBundle\Mapper;

/**
 * Allows map client mimeType to well-known system mimeType.
 */
interface ClientMimeTypeMapperInterface
{
    public function addMapping(string $originalMimeType, string $returnedMimeType): void;
    public function getMimeType(string $originalMimeType): string;
}
