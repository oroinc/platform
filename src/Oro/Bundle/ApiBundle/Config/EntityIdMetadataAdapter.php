<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;

/**
 * Extracts information required to manage the identifier of an entity from the entity configuration.
 */
class EntityIdMetadataAdapter implements EntityIdMetadataInterface
{
    private string $className;
    private EntityDefinitionConfig $config;

    public function __construct(string $className, EntityDefinitionConfig $config)
    {
        $this->className = $className;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierFieldNames(): array
    {
        return $this->config->getIdentifierFieldNames();
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyPath(string $propertyName): ?string
    {
        return $this->config->getField($propertyName)?->getPropertyPath($propertyName);
    }

    /**
     * {@inheritDoc}
     */
    public function getHints(): array
    {
        return $this->config->getHints();
    }
}
