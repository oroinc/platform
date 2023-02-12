<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\ApiContext;

/**
 * The execution context for processors for "get_metadata" action.
 * @method EntityMetadata|null getResult()
 */
class MetadataContext extends ApiContext
{
    /** FQCN of an entity */
    private const CLASS_NAME = 'class';

    /** the name of the action for which the metadata is built */
    private const TARGET_ACTION = 'targetAction';

    /** a list of requests for additional metadata information that should be retrieved */
    private const EXTRA = 'extra';

    /** whether excluded fields and associations should not be removed */
    private const WITH_EXCLUDED_PROPERTIES = 'withExcludedProperties';

    /** @var MetadataExtraInterface[] */
    private array $extras = [];
    private EntityDefinitionConfig $config;

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->set(self::EXTRA, []);
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
     * Gets the name of the action for which the metadata is built
     */
    public function getTargetAction(): ?string
    {
        return $this->get(self::TARGET_ACTION);
    }

    /**
     * Sets the name of the action for which the metadata is built
     */
    public function setTargetAction(?string  $action): void
    {
        if ($action) {
            $this->set(self::TARGET_ACTION, $action);
        } else {
            $this->remove(self::TARGET_ACTION);
        }
    }

    /**
     * Gets the configuration of an entity.
     */
    public function getConfig(): EntityDefinitionConfig
    {
        return $this->config;
    }

    /**
     * Sets the configuration of an entity.
     */
    public function setConfig(EntityDefinitionConfig $definition): void
    {
        $this->config = $definition;
    }

    /**
     * Checks if the specified additional metadata is requested.
     */
    public function hasExtra(string $extraName): bool
    {
        return \in_array($extraName, $this->get(self::EXTRA), true);
    }

    /**
     * Gets additional metadata if it was requested.
     */
    public function getExtra(string $extraName): ?MetadataExtraInterface
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
     * Gets a list of requested additional metadata.
     *
     * @return MetadataExtraInterface[]
     */
    public function getExtras(): array
    {
        return $this->extras;
    }

    /**
     * Sets additional metadata that you need.
     *
     * @param MetadataExtraInterface[] $extras
     *
     * @throws \InvalidArgumentException if $extras has invalid elements
     */
    public function setExtras(array $extras): void
    {
        $names = [];
        foreach ($extras as $extra) {
            if (!$extra instanceof MetadataExtraInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Expected an array of "%s".',
                    MetadataExtraInterface::class
                ));
            }
            $names[] = $extra->getName();
            $extra->configureContext($this);
        }

        $this->extras = $extras;
        $this->set(self::EXTRA, $names);
    }

    /**
     * Gets a flag indicates whether excluded fields and associations should not be removed.
     */
    public function getWithExcludedProperties(): bool
    {
        return (bool)$this->get(self::WITH_EXCLUDED_PROPERTIES);
    }

    /**
     * Sets a flag indicates whether excluded fields and associations should not be removed.
     */
    public function setWithExcludedProperties(bool $flag): void
    {
        $this->set(self::WITH_EXCLUDED_PROPERTIES, $flag);
    }
}
