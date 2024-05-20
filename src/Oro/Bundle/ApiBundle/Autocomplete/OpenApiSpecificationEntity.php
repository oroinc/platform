<?php

namespace Oro\Bundle\ApiBundle\Autocomplete;

/**
 * Represents an entity for OpenAPI specification.
 */
class OpenApiSpecificationEntity
{
    public function __construct(
        private string $id,
        private string $name
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
