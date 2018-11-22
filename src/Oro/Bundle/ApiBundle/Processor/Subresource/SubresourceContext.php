<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\ConfigExtraCollection;
use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraCollection;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Context;

/**
 * The base execution context for processors for subresources and relationships related actions,
 * such as "get_subresource", "update_subresource", "add_subresource", "delete_subresource",
 * "get_relationship", "update_relationship", "add_relationship" and "delete_relationship".
 */
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

    /** a configuration of the parent entity */
    const PARENT_CONFIG = 'parentConfig';

    /** metadata of the parent entity */
    const PARENT_METADATA = 'parentMetadata';

    /** @var ConfigExtraCollection|null */
    private $parentConfigExtras;

    /** @var MetadataExtraCollection|null */
    private $parentMetadataExtras;

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
        $this->ensureParentConfigExtrasInitialized();

        return $this->parentConfigExtras->getConfigExtras();
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
        if (empty($extras)) {
            $this->parentConfigExtras = null;
        } else {
            if (null === $this->parentConfigExtras) {
                $this->parentConfigExtras = new ConfigExtraCollection();
            }
            $this->parentConfigExtras->setConfigExtras($extras);
        }
    }

    /**
     * Checks whether some configuration data of the parent entity is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasParentConfigExtra($extraName)
    {
        $this->ensureParentConfigExtrasInitialized();

        return $this->parentConfigExtras->hasConfigExtra($extraName);
    }

    /**
     * Gets a request for configuration data of the parent entity by its name.
     *
     * @param string $extraName
     *
     * @return ConfigExtraInterface|null
     */
    public function getParentConfigExtra($extraName)
    {
        $this->ensureParentConfigExtrasInitialized();

        return $this->parentConfigExtras->getConfigExtra($extraName);
    }

    /**
     * Adds a request for some configuration data of the parent entity.
     *
     * @param ConfigExtraInterface $extra
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addParentConfigExtra(ConfigExtraInterface $extra)
    {
        $this->ensureParentConfigExtrasInitialized();
        $this->parentConfigExtras->addConfigExtra($extra);
    }

    /**
     * Removes a request for some configuration data of the parent entity.
     *
     * @param string $extraName
     */
    public function removeParentConfigExtra($extraName)
    {
        $this->ensureParentConfigExtrasInitialized();
        $this->parentConfigExtras->removeConfigExtra($extraName);
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
            new EntityDefinitionConfigExtra(
                $this->getAction(),
                $this->isCollection(),
                $this->getParentClassName(),
                $this->getAssociationName()
            ),
            new CustomizeLoadedDataConfigExtra(),
            new DataTransformersConfigExtra(),
            new FilterFieldsConfigExtra([$this->getParentClassName() => [$this->getAssociationName()]])
        ];
    }

    /**
     * Makes sure that a list of requests for configuration data of the parent entity is initialized.
     */
    private function ensureParentConfigExtrasInitialized()
    {
        if (null === $this->parentConfigExtras) {
            $this->parentConfigExtras = new ConfigExtraCollection();
            $this->parentConfigExtras->setConfigExtras($this->createParentConfigExtras());
        }
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
            $config = $this->loadEntityConfig($parentEntityClass, $this->getParentConfigExtras());
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
        $this->ensureParentMetadataExtrasInitialized();

        return $this->parentMetadataExtras->getMetadataExtras();
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
        if (empty($extras)) {
            $this->parentMetadataExtras = null;
        } else {
            if (null === $this->parentMetadataExtras) {
                $this->parentMetadataExtras = new MetadataExtraCollection();
            }
            $this->parentMetadataExtras->setMetadataExtras($extras);
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
    public function setParentMetadata(?EntityMetadata $metadata)
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
        $extras = [];
        $action = $this->getAction();
        if ($action) {
            $extras[] = new ActionMetadataExtra($action);
        }

        return $extras;
    }

    /**
     * Makes sure that a list of requests for additional metadata info of the parent entity is initialized.
     */
    private function ensureParentMetadataExtrasInitialized()
    {
        if (null === $this->parentMetadataExtras) {
            $this->parentMetadataExtras = new MetadataExtraCollection();
            $this->parentMetadataExtras->setMetadataExtras($this->createParentMetadataExtras());
        }
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
            $extras = $this->getParentMetadataExtras();
            if ($this->isHateoasEnabled()) {
                $extras[] = new HateoasMetadataExtra($this->getFilterValues());
            }
            $metadata = $this->metadataProvider->getMetadata(
                $parentEntityClass,
                $this->getVersion(),
                $this->getRequestType(),
                $this->getParentConfig(),
                $extras
            );
            $this->set(self::PARENT_METADATA, $metadata);
        } catch (\Exception $e) {
            $this->set(self::PARENT_METADATA, null);

            throw $e;
        }
    }
}
