<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraCollection;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraCollection;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * The base execution context for processors for subresources and relationships related actions,
 * such as "get_subresource", "update_subresource", "add_subresource", "delete_subresource",
 * "get_relationship", "update_relationship", "add_relationship" and "delete_relationship".
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SubresourceContext extends Context
{
    /** FQCN of the parent entity */
    const PARENT_CLASS_NAME = 'parentClass';

    /** the association name the sub-resource represents */
    const ASSOCIATION = 'association';

    /** a flag indicates if an association represents "to-many" or "to-one" relationship */
    const COLLECTION = 'collection';

    /** a configuration of the parent entity */
    const PARENT_CONFIG = 'parentConfig';

    /** metadata of the parent entity */
    const PARENT_METADATA = 'parentMetadata';

    /** @var mixed */
    private $parentId;

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
        return $this->parentId;
    }

    /**
     * Sets an identifier of the parent entity.
     *
     * @param mixed $parentId
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
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
     * Whether an association represents "to-many" or "to-one" relationship.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->get(self::COLLECTION);
    }

    /**
     * Sets a flag indicates whether an association represents "to-many" or "to-one" relationship.
     *
     * @param bool $value TRUE for "to-many" relationship, FALSE for "to-one" relationship
     */
    public function setIsCollection($value)
    {
        $this->set(self::COLLECTION, $value);
    }

    /**
     * Gets the target base class for the association the sub-resource represents.
     * E.g. if an association is bases on Doctrine's inheritance mapping,
     * the target class will be Oro\Bundle\ApiBundle\Model\EntityIdentifier
     * and the base target class will be a mapped superclass
     * or a parent class for table inheritance association.
     *
     * @return string|null
     */
    public function getAssociationBaseTargetClassName()
    {
        $parentMetadata = $this->getParentMetadata();
        if (null === $parentMetadata) {
            return null;
        }
        $associationMetadata = $parentMetadata->getAssociation($this->getAssociationName());
        if (null === $associationMetadata) {
            return null;
        }

        return $associationMetadata->getBaseTargetClassName();
    }

    /**
     * {@inheritdoc}
     */
    public function getManageableEntityClass(DoctrineHelper $doctrineHelper)
    {
        $entityClass = $this->getClassName();
        if (is_a($entityClass, EntityIdentifier::class, true)) {
            $entityClass = $this->getAssociationBaseTargetClassName();
        }
        if ($entityClass) {
            $entityClass = $doctrineHelper->getManageableEntityClass(
                $entityClass,
                $this->getConfig()
            );
        }

        return $entityClass;
    }

    /**
     * Returns the parent class of API resource if it is a manageable entity;
     * otherwise, checks if the parent API resource is based on a manageable entity, and if so,
     * returns the class name of this entity.
     *
     * @param DoctrineHelper $doctrineHelper
     *
     * @return string|null
     */
    public function getManageableParentEntityClass(DoctrineHelper $doctrineHelper)
    {
        return $doctrineHelper->getManageableEntityClass(
            $this->getParentClassName(),
            $this->getParentConfig()
        );
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
            if ($this->isHateoasEnabled() && !$this->parentConfigExtras->hasConfigExtra(HateoasConfigExtra::NAME)) {
                $this->parentConfigExtras->addConfigExtra(new HateoasConfigExtra());
            }
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
     * {@inheritdoc}
     */
    public function setHateoas(bool $flag)
    {
        parent::setHateoas($flag);
        if (null !== $this->parentConfigExtras) {
            if (!$flag) {
                $this->parentConfigExtras->removeConfigExtra(HateoasConfigExtra::NAME);
            } elseif (!$this->parentConfigExtras->hasConfigExtra(HateoasConfigExtra::NAME)) {
                $this->parentConfigExtras->addConfigExtra(new HateoasConfigExtra());
            }
        }
        if (null !== $this->parentMetadataExtras) {
            if (!$flag) {
                $this->parentMetadataExtras->removeMetadataExtra(HateoasMetadataExtra::NAME);
            } elseif (!$this->parentMetadataExtras->hasMetadataExtra(HateoasMetadataExtra::NAME)) {
                $this->parentMetadataExtras->addMetadataExtra(new HateoasMetadataExtra($this->getFilterValues()));
            }
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
        $extras = [
            new EntityDefinitionConfigExtra(
                $this->getAction(),
                $this->isCollection(),
                $this->getParentClassName(),
                $this->getAssociationName()
            ),
            new FilterFieldsConfigExtra([$this->getParentClassName() => [$this->getAssociationName()]])
        ];
        if ($this->isHateoasEnabled()) {
            $extras[] = new HateoasConfigExtra();
        }

        return $extras;
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
            if ($this->isHateoasEnabled()
                && !$this->parentMetadataExtras->hasMetadataExtra(HateoasMetadataExtra::NAME)
            ) {
                $this->parentMetadataExtras->addMetadataExtra(new HateoasMetadataExtra($this->getFilterValues()));
            }
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
        if ($this->isHateoasEnabled()) {
            $extras[] = new HateoasMetadataExtra($this->getFilterValues());
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
            $metadata = $this->metadataProvider->getMetadata(
                $parentEntityClass,
                $this->getVersion(),
                $this->getRequestType(),
                $this->getParentConfig(),
                $this->getParentMetadataExtras()
            );
            $this->set(self::PARENT_METADATA, $metadata);
        } catch (\Exception $e) {
            $this->set(self::PARENT_METADATA, null);

            throw $e;
        }
    }
}
