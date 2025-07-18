<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Exception;

/**
 * Thrown when a PDF document source entity is not found.
 */
class PdfDocumentSourceEntityNotFound extends PdfDocumentException
{
    public static function factory(string $sourceEntityClass, ?int $sourceEntityId): self
    {
        return new self(
            sprintf(
                'The source entity "%s" with ID "%s" was not found.',
                $sourceEntityClass,
                $sourceEntityId,
            )
        );
    }
}
