<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Processor\ApiContext;

/**
 * The execution context for processors for "get_config" action.
 * @method EntityDefinitionConfig getResult()
 */
class ConfigContext extends ApiContext
{
    /** FQCN of an entity */
    private const CLASS_NAME = 'class';

    /** the name of the action for which the configuration is built */
    private const TARGET_ACTION = 'targetAction';

    /** a flag indicates whether a configuration is requested for a list of entities or a single entity */
    private const COLLECTION = 'collection';

    /** FQCN of the parent entity if a configuration is requested for a sub-resource */
    private const PARENT_CLASS_NAME = 'parentClass';

    /** the association name if a configuration is requested for a sub-resource */
    private const ASSOCIATION = 'association';

    /** the maximum number of related entities that can be retrieved */
    private const MAX_RELATED_ENTITIES = 'maxRelatedEntities';

    /** a list of requests for configuration data that should be retrieved */
    private const EXTRA = 'extra';

    /** the exclusion policy that was before CompleteDefinition processor is set it to "all" */
    private const REQUESTED_EXCLUSION_POLICY = 'requested_exclusion_policy';

    /** @var ConfigExtraInterface[] */
    private array $extras = [];
    /** @var string[]|null */
    private ?array $explicitlyConfiguredFieldNames = null;

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->set(self::EXTRA, []);
        $this->set(self::REQUESTED_EXCLUSION_POLICY, null);
    }

    /**
     * Gets FQCN of an entity.
     */
    public function getClassName(): string
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     */
    public function setClassName(string $className): void
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Gets the name of the action for which the configuration is built
     */
    public function getTargetAction(): ?string
    {
        return $this->get(self::TARGET_ACTION);
    }

    /**
     * Sets the name of the action for which the configuration is built
     */
    public function setTargetAction(string $action): void
    {
        $this->set(self::TARGET_ACTION, $action);
    }

    /**
     * Indicates whether a configuration is requested for a list of entities or a single entity.
     */
    public function isCollection(): bool
    {
        return (bool)$this->get(self::COLLECTION);
    }

    /**
     * Sets a flag indicates whether a configuration is requested for a list of entities or a single entity.
     */
    public function setIsCollection(bool $value): void
    {
        $this->set(self::COLLECTION, $value);
    }

    /**
     * Gets FQCN of the parent entity if a configuration is requested for a sub-resource.
     */
    public function getParentClassName(): ?string
    {
        return $this->get(self::PARENT_CLASS_NAME);
    }

    /**
     * Sets FQCN of the parent entity if a configuration is requested for a sub-resource.
     */
    public function setParentClassName(string $parentClassName): void
    {
        $this->set(self::PARENT_CLASS_NAME, $parentClassName);
    }

    /**
     * Gets the association name if a configuration is requested for a sub-resource.
     */
    public function getAssociationName(): ?string
    {
        return $this->get(self::ASSOCIATION);
    }

    /**
     * Sets the association name if a configuration is requested for a sub-resource.
     */
    public function setAssociationName(string $associationName): void
    {
        $this->set(self::ASSOCIATION, $associationName);
    }

    /**
     * Gets the maximum number of related entities that can be retrieved
     */
    public function getMaxRelatedEntities(): ?int
    {
        return $this->get(self::MAX_RELATED_ENTITIES);
    }

    /**
     * Sets the maximum number of related entities that can be retrieved
     */
    public function setMaxRelatedEntities(?int $limit): void
    {
        if (null === $limit) {
            $this->remove(self::MAX_RELATED_ENTITIES);
        } else {
            $this->set(self::MAX_RELATED_ENTITIES, $limit);
        }
    }

    /**
     * Gets the exclusion policy that was before CompleteDefinition processor is set it to "all".
     */
    public function getRequestedExclusionPolicy(): ?string
    {
        return $this->get(self::REQUESTED_EXCLUSION_POLICY);
    }

    /**
     * Sets the exclusion policy that was before CompleteDefinition processor is set it to "all".
     */
    public function setRequestedExclusionPolicy(?string $exclusionPolicy): void
    {
        $this->set(self::REQUESTED_EXCLUSION_POLICY, $exclusionPolicy ?: null);
    }

    /**
     * Gets the names of fields that were configured explicitly in "Resources/config/oro/api.yml".
     *
     * @return string[]
     */
    public function getExplicitlyConfiguredFieldNames(): array
    {
        return $this->explicitlyConfiguredFieldNames ?? [];
    }

    /**
     * Sets the names of fields that were configured explicitly in "Resources/config/oro/api.yml".
     *
     * @param string[] $fieldNames
     */
    public function setExplicitlyConfiguredFieldNames(array $fieldNames): void
    {
        $this->explicitlyConfiguredFieldNames = $fieldNames;
    }

    /**
     * Checks whether some configuration data is requested.
     */
    public function hasExtra(string $extraName): bool
    {
        return \in_array($extraName, $this->get(self::EXTRA), true);
    }

    /**
     * Gets a request for configuration data.
     */
    public function getExtra(string $extraName): ?ConfigExtraInterface
    {
        $result = null;
        foreach ($this->extras as $extra) {
            if ($extra->getName() === $extraName) {
                $result = $extra;
                break;
            }
        }

        return $result;
    }

    /**
     * Gets a list of requests for configuration data.
     *
     * @return ConfigExtraInterface[]
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * Sets requests for configuration data.
     *
     * @param ConfigExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setExtras(array $extras): void
    {
        $names = [];
        foreach ($extras as $extra) {
            if (!$extra instanceof ConfigExtraInterface) {
                throw new \InvalidArgumentException(sprintf('Expected an array of "%s".', ConfigExtraInterface::class));
            }
            $names[] = $extra->getName();
            $extra->configureContext($this);
        }

        $this->extras = $extras;
        $this->set(self::EXTRA, $names);
    }

    /**
     * Removes a request for some configuration data.
     */
    public function removeExtra(string $extraName): void
    {
        $keys = array_keys($this->extras);
        foreach ($keys as $key) {
            if ($this->extras[$key]->getName() === $extraName) {
                unset($this->extras[$key]);
            }
        }
        $this->extras = array_values($this->extras);

        $names = $this->get(self::EXTRA);
        $key = array_search($extraName, $names, true);
        if (false !== $key) {
            unset($names[$key]);
            $this->set(self::EXTRA, array_values($names));
        }
    }

    /**
     * Gets a list of requests for configuration data that can be used
     * to get configuration of related entities.
     *
     * @return ConfigExtraInterface[]
     */
    public function getPropagableExtras(): array
    {
        $result = [];
        foreach ($this->extras as $extra) {
            if ($extra->isPropagable()) {
                $result[] = $extra;
            }
        }

        return $result;
    }

    /**
     * Checks whether a definition of filters exists.
     */
    public function hasFilters(): bool
    {
        return $this->has(FiltersConfigExtra::NAME);
    }

    /**
     * Gets a definition of filters.
     */
    public function getFilters(): ?FiltersConfig
    {
        return $this->get(FiltersConfigExtra::NAME);
    }

    /**
     * Sets a definition of filters.
     */
    public function setFilters(?FiltersConfig $filters): void
    {
        $this->set(FiltersConfigExtra::NAME, $filters);
    }

    /**
     * Checks whether a definition of sorters exists.
     */
    public function hasSorters(): bool
    {
        return $this->has(SortersConfigExtra::NAME);
    }

    /**
     * Gets a definition of sorters.
     */
    public function getSorters(): ?SortersConfig
    {
        return $this->get(SortersConfigExtra::NAME);
    }

    /**
     * Sets a definition of sorters.
     */
    public function setSorters(?SortersConfig $sorters): void
    {
        $this->set(SortersConfigExtra::NAME, $sorters);
    }
}
