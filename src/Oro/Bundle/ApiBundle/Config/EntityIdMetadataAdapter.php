<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;

/**
 * Extracts information required to manage the identifier of an entity from the entity configuration.
 */
class EntityIdMetadataAdapter implements EntityIdMetadataInterface
{
    /** @var string */
    private $className;

    /** @var EntityDefinitionConfig */
    private $config;

    public function __construct(string $className, EntityDefinitionConfig $config)
    {
        $this->className = $className;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierFieldNames()
    {
        return $this->config->getIdentifierFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyPath($propertyName)
    {
        $field = $this->config->getField($propertyName);
        if (null === $field) {
            return null;
        }

        return $field->getPropertyPath($propertyName);
    }
}
