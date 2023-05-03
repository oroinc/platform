<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraCollection;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
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
    private const PARENT_CLASS_NAME = 'parentClass';

    /** the association name the sub-resource represents */
    private const ASSOCIATION = 'association';

    /** a flag indicates if an association represents "to-many" or "to-one" relationship */
    private const COLLECTION = 'collection';

    /** a configuration of the parent entity */
    private const PARENT_CONFIG = 'parentConfig';

    /** metadata of the parent entity */
    private const PARENT_METADATA = 'parentMetadata';

    private mixed $parentId = null;
    private ?ConfigExtraCollection $parentConfigExtras = null;
    private ?MetadataExtraCollection $parentMetadataExtras = null;

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->set(self::COLLECTION, false);
    }

    /**
     * Gets FQCN of the parent entity.
     */
    public function getParentClassName(): ?string
    {
        return $this->get(self::PARENT_CLASS_NAME);
    }

    /**
     * Sets FQCN of the parent entity.
     */
    public function setParentClassName(?string $parentClassName): void
    {
        if (null === $parentClassName) {
            $this->remove(self::PARENT_CLASS_NAME);
        } else {
            $this->set(self::PARENT_CLASS_NAME, $parentClassName);
        }
    }

    /**
     * Gets an identifier of the parent entity.
     */
    public function getParentId(): mixed
    {
        return $this->parentId;
    }

    /**
     * Sets an identifier of the parent entity.
     */
    public function setParentId(mixed $parentId): void
    {
        $this->parentId = $parentId;
    }

    /**
     * Gets the association name the sub-resource represents.
     */
    public function getAssociationName(): ?string
    {
        return $this->get(self::ASSOCIATION);
    }

    /**
     * Sets the association name the sub-resource represents.
     */
    public function setAssociationName(?string $associationName): void
    {
        if (null === $associationName) {
            $this->remove(self::ASSOCIATION);
        } else {
            $this->set(self::ASSOCIATION, $associationName);
        }
    }

    /**
     * Whether an association represents "to-many" or "to-one" relationship.
     */
    public function isCollection(): bool
    {
        return (bool)$this->get(self::COLLECTION);
    }

    /**
     * Sets a flag indicates whether an association represents "to-many" or "to-one" relationship.
     */
    public function setIsCollection(bool $value): void
    {
        $this->set(self::COLLECTION, $value);
    }

    /**
     * Gets the target base class for the association the sub-resource represents.
     * E.g. if an association is bases on Doctrine's inheritance mapping,
     * the target class will be Oro\Bundle\ApiBundle\Model\EntityIdentifier
     * and the base target class will be a mapped superclass
     * or a parent class for table inheritance association.
     */
    public function getAssociationBaseTargetClassName(): ?string
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
     * {@inheritDoc}
     */
    public function getManageableEntityClass(DoctrineHelper $doctrineHelper): ?string
    {
        $entityClass = $this->getClassName();
        if (is_a($entityClass, EntityIdentifier::class, true)) {
            $entityClass = $this->getAssociationBaseTargetClassName();
        }
        if ($entityClass) {
            $entityClass = $doctrineHelper->getManageableEntityClass($entityClass, $this->getConfig());
        }

        return $entityClass;
    }

    /**
     * Returns the parent class of API resource if it is a manageable entity;
     * otherwise, checks if the parent API resource is based on a manageable entity, and if so,
     * returns the class name of this entity.
     */
    public function getManageableParentEntityClass(DoctrineHelper $doctrineHelper): ?string
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
    public function getParentConfigExtras(): array
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
    public function setParentConfigExtras(array $extras): void
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
     */
    public function hasParentConfigExtra(string $extraName): bool
    {
        $this->ensureParentConfigExtrasInitialized();

        return $this->parentConfigExtras->hasConfigExtra($extraName);
    }

    /**
     * Gets a request for configuration data of the parent entity by its name.
     */
    public function getParentConfigExtra(string $extraName): ?ConfigExtraInterface
    {
        $this->ensureParentConfigExtrasInitialized();

        return $this->parentConfigExtras->getConfigExtra($extraName);
    }

    /**
     * Adds a request for some configuration data of the parent entity.
     *
     * @throws \InvalidArgumentException if a config extra with the same name already exists
     */
    public function addParentConfigExtra(ConfigExtraInterface $extra): void
    {
        $this->ensureParentConfigExtrasInitialized();
        $this->parentConfigExtras->addConfigExtra($extra);
    }

    /**
     * Removes a request for some configuration data of the parent entity.
     */
    public function removeParentConfigExtra(string $extraName): void
    {
        $this->ensureParentConfigExtrasInitialized();
        $this->parentConfigExtras->removeConfigExtra($extraName);
    }

    /**
     * {@inheritDoc}
     */
    public function setHateoas(bool $flag): void
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
     */
    public function hasParentConfig(): bool
    {
        return $this->has(self::PARENT_CONFIG);
    }

    /**
     * Gets a configuration of the parent entity.
     */
    public function getParentConfig(): ?EntityDefinitionConfig
    {
        if (!$this->has(self::PARENT_CONFIG)) {
            $this->loadParentConfig();
        }

        return $this->get(self::PARENT_CONFIG);
    }

    /**
     * Sets a configuration of the parent entity.
     */
    public function setParentConfig(?EntityDefinitionConfig $definition): void
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
    protected function createParentConfigExtras(): array
    {
        $extras = [new EntityDefinitionConfigExtra($this->get(self::ACTION))];
        if ($this->isHateoasEnabled()) {
            $extras[] = new HateoasConfigExtra();
        }

        return $extras;
    }

    /**
     * Makes sure that a list of requests for configuration data of the parent entity is initialized.
     */
    private function ensureParentConfigExtrasInitialized(): void
    {
        if (null === $this->parentConfigExtras) {
            $this->parentConfigExtras = new ConfigExtraCollection();
            $this->parentConfigExtras->setConfigExtras($this->createParentConfigExtras());
        }
    }

    /**
     * Loads the parent entity configuration.
     */
    protected function loadParentConfig(): void
    {
        $parentEntityClass = $this->get(self::PARENT_CLASS_NAME);
        if (!$parentEntityClass) {
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
    public function getParentMetadataExtras(): array
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
    public function setParentMetadataExtras(array $extras): void
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
     */
    public function hasParentMetadata(): bool
    {
        return $this->has(self::PARENT_METADATA);
    }

    /**
     * Gets metadata of the parent entity.
     */
    public function getParentMetadata(): ?EntityMetadata
    {
        if (!$this->has(self::PARENT_METADATA)) {
            $this->loadParentMetadata();
        }

        return $this->get(self::PARENT_METADATA);
    }

    /**
     * Sets metadata of the parent entity.
     */
    public function setParentMetadata(?EntityMetadata $metadata): void
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
    protected function createParentMetadataExtras(): array
    {
        $extras = [];
        $action = $this->get(self::ACTION);
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
    private function ensureParentMetadataExtrasInitialized(): void
    {
        if (null === $this->parentMetadataExtras) {
            $this->parentMetadataExtras = new MetadataExtraCollection();
            $this->parentMetadataExtras->setMetadataExtras($this->createParentMetadataExtras());
        }
    }

    /**
     * Loads the parent entity metadata.
     */
    protected function loadParentMetadata(): void
    {
        $parentEntityClass = $this->get(self::PARENT_CLASS_NAME);
        if (!$parentEntityClass) {
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
