<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

class SubresourceContext extends Context
{
    /** FQCN of the parent entity */
    const PARENT_CLASS_NAME = 'parentClass';

    /** an identifier of the parent entity */
    const PARENT_ID = 'parentId';

    /** the association name the sub-resource represents */
    const ASSOCIATION = 'association';

    /** a flag indicates if an association represents "to-many" or "to-one" relation */
    const COLLECTION = 'collection';

    /** the parent entity object */
    const PARENT_ENTITY = 'parentEntity';

    /** a list of requests for configuration data of the parent entity */
    const PARENT_CONFIG_EXTRAS = 'parentConfigExtras';

    /** a configuration of the parent entity */
    const PARENT_CONFIG = 'parentConfig';

    /** a list of requests for additional metadata info of the parent entity */
    const PARENT_METADATA_EXTRAS = 'parentMetadataExtras';

    /** metadata of the parent entity */
    const PARENT_METADATA = 'parentMetadata';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->set(self::COLLECTION, false);
    }

    /**
     * Gets FQCN of the parent entity.
     *
     * @return string
     */
    public function getParentClassName()
    {
        return $this->get(self::PARENT_CLASS_NAME);
    }

    /**
     * Sets FQCN of the parent entity.
     *
     * @param string $parentClassName
     */
    public function setParentClassName($parentClassName)
    {
        $this->set(self::PARENT_CLASS_NAME, $parentClassName);
    }

    /**
     * Gets an identifier of the parent entity.
     *
     * @return mixed
     */
    public function getParentId()
    {
        return $this->get(self::PARENT_ID);
    }

    /**
     * Sets an identifier of the parent entity.
     *
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->set(self::PARENT_ID, $parentId);
    }

    /**
     * Gets the association name the sub-resource represents.
     *
     * @return string
     */
    public function getAssociationName()
    {
        return $this->get(self::ASSOCIATION);
    }

    /**
     * Sets the association name the sub-resource represents.
     *
     * @param string $associationName
     */
    public function setAssociationName($associationName)
    {
        $this->set(self::ASSOCIATION, $associationName);
    }

    /**
     * Whether an association represents "to-many" or "to-one" relation.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->get(self::COLLECTION);
    }

    /**
     * Sets a flag indicates whether an association represents "to-many" or "to-one" relation.
     *
     * @param bool $value TRUE for "to-many" relation, FALSE for "to-one" relation
     */
    public function setIsCollection($value)
    {
        $this->set(self::COLLECTION, $value);
    }

    /**
     * Checks whether the parent entity exists.
     *
     * @return bool
     */
    public function hasParentEntity()
    {
        return $this->has(self::PARENT_ENTITY);
    }

    /**
     * Gets the parent entity object.
     *
     * @return object|null
     */
    public function getParentEntity()
    {
        return $this->get(self::PARENT_ENTITY);
    }

    /**
     * Sets the parent entity object.
     *
     * @param object|null $parentEntity
     */
    public function setParentEntity($parentEntity)
    {
        $this->set(self::PARENT_ENTITY, $parentEntity);
    }

    /**
     * Gets a list of requests for configuration data of the parent entity.
     *
     * @return ConfigExtraInterface[]
     */
    public function getParentConfigExtras()
    {
        if (!$this->has(self::PARENT_CONFIG_EXTRAS)) {
            $this->set(self::PARENT_CONFIG_EXTRAS, $this->createParentConfigExtras());
        }

        return $this->get(self::PARENT_CONFIG_EXTRAS);
    }

    /**
     * Sets a list of requests for configuration data of the parent entity.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setParentConfigExtras(array $extras)
    {
        foreach ($extras as $configExtra) {
            if (!$configExtra instanceof ConfigExtraInterface) {
                throw new \InvalidArgumentException(
                    'Expected an array of "Oro\Bundle\ApiBundle\Config\ConfigExtraInterface".'
                );
            }
        }

        if (empty($extras)) {
            $this->remove(self::PARENT_CONFIG_EXTRAS);
        } else {
            $this->set(self::PARENT_CONFIG_EXTRAS, $extras);
        }
    }

    /**
     * Checks whether a configuration of the parent entity exists.
     *
     * @return bool
     */
    public function hasParentConfig()
    {
        return $this->has(self::PARENT_CONFIG);
    }

    /**
     * Gets a configuration of the parent entity.
     *
     * @return EntityDefinitionConfig|null
     */
    public function getParentConfig()
    {
        if (!$this->has(self::PARENT_CONFIG)) {
            $this->loadParentConfig();
        }

        return $this->get(self::PARENT_CONFIG);
    }

    /**
     * Sets a configuration of the parent entity.
     *
     * @param EntityDefinitionConfig|null $definition
     */
    public function setParentConfig(EntityDefinitionConfig $definition = null)
    {
        if ($definition) {
            $this->set(self::PARENT_CONFIG, $definition);
        } else {
            $this->remove(self::PARENT_CONFIG);
        }
    }

    /**
     * Creates a list of requests for configuration data of the parent entity.
     *
     * @return ConfigExtraInterface[]
     */
    protected function createParentConfigExtras()
    {
        return [
            new EntityDefinitionConfigExtra(),
            new FilterFieldsConfigExtra([$this->getParentClassName() => [$this->getAssociationName()]])
        ];
    }

    /**
     * Loads the parent entity configuration.
     */
    protected function loadParentConfig()
    {
        $parentEntityClass = $this->getParentClassName();
        if (empty($parentEntityClass)) {
            $this->set(self::PARENT_CONFIG, null);

            throw new RuntimeException(
                'The parent entity class name must be set in the context before a configuration is loaded.'
            );
        }

        try {
            $config = $this->configProvider->getConfig(
                $parentEntityClass,
                $this->getVersion(),
                $this->getRequestType(),
                $this->getParentConfigExtras()
            );
            $this->set(self::PARENT_CONFIG, $config->getDefinition());
        } catch (\Exception $e) {
            $this->set(self::PARENT_CONFIG, null);

            throw $e;
        }
    }

    /**
     * Gets a list of requests for additional metadata info of the parent entity.
     *
     * @return MetadataExtraInterface[]
     */
    public function getParentMetadataExtras()
    {
        if (!$this->has(self::PARENT_METADATA_EXTRAS)) {
            $this->set(self::PARENT_METADATA_EXTRAS, $this->createParentMetadataExtras());
        }

        return $this->get(self::PARENT_METADATA_EXTRAS);
    }

    /**
     * Sets a list of requests for additional metadata info of the parent entity.
     *
     * @param MetadataExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setParentMetadataExtras(array $extras)
    {
        foreach ($extras as $metadataExtra) {
            if (!$metadataExtra instanceof MetadataExtraInterface) {
                throw new \InvalidArgumentException(
                    'Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".'
                );
            }
        }

        if (empty($extras)) {
            $this->remove(self::PARENT_METADATA_EXTRAS);
        } else {
            $this->set(self::PARENT_METADATA_EXTRAS, $extras);
        }
    }

    /**
     * Checks whether metadata of the parent entity exists.
     *
     * @return bool
     */
    public function hasParentMetadata()
    {
        return $this->has(self::PARENT_METADATA);
    }

    /**
     * Gets metadata of the parent entity.
     *
     * @return EntityMetadata|null
     */
    public function getParentMetadata()
    {
        if (!$this->has(self::PARENT_METADATA)) {
            $this->loadParentMetadata();
        }

        return $this->get(self::PARENT_METADATA);
    }

    /**
     * Sets metadata of the parent entity.
     *
     * @param EntityMetadata|null $metadata
     */
    public function setParentMetadata(EntityMetadata $metadata = null)
    {
        if ($metadata) {
            $this->set(self::PARENT_METADATA, $metadata);
        } else {
            $this->remove(self::PARENT_METADATA);
        }
    }

    /**
     * Creates a list of requests for additional metadata info of the parent entity.
     *
     * @return MetadataExtraInterface[]
     */
    protected function createParentMetadataExtras()
    {
        return [];
    }

    /**
     * Loads the parent entity metadata.
     */
    protected function loadParentMetadata()
    {
        $parentEntityClass = $this->getParentClassName();
        if (empty($parentEntityClass)) {
            $this->set(self::PARENT_METADATA, null);

            return;
        }

        try {
            $metadata = $this->metadataProvider->getMetadata(
                $parentEntityClass,
                $this->getVersion(),
                $this->getRequestType(),
                $this->getParentMetadataExtras(),
                $this->getParentConfig()
            );
            $this->set(self::PARENT_METADATA, $metadata);
        } catch (\Exception $e) {
            $this->set(self::PARENT_METADATA, null);

            throw $e;
        }
    }
}
