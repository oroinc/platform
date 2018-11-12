<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\ApiContext;

/**
 * The execution context for processors for "get_metadata" action.
 * @method EntityMetadata|null getResult()
 */
class MetadataContext extends ApiContext
{
    /** FQCN of an entity */
    const CLASS_NAME = 'class';

    /** the name of the action for which the metadata is built */
    const TARGET_ACTION = 'targetAction';

    /** a list of requests for additional metadata information that should be retrieved */
    const EXTRA = 'extra';

    /** whether excluded fields and associations should not be removed */
    const WITH_EXCLUDED_PROPERTIES = 'withExcludedProperties';

    /** @var MetadataExtraInterface[] */
    protected $extras = [];

    /** @var EntityDefinitionConfig */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        $this->set(self::EXTRA, []);
    }

    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     *
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Gets the name of the action for which the metadata is built
     *
     * @return string|null
     */
    public function getTargetAction()
    {
        return $this->get(self::TARGET_ACTION);
    }

    /**
     * Sets the name of the action for which the metadata is built
     *
     * @param string|null $action
     */
    public function setTargetAction($action)
    {
        if ($action) {
            $this->set(self::TARGET_ACTION, $action);
        } else {
            $this->remove(self::TARGET_ACTION);
        }
    }

    /**
     * Gets the configuration of an entity.
     *
     * @return EntityDefinitionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Sets the configuration of an entity.
     *
     * @param EntityDefinitionConfig $definition
     */
    public function setConfig(EntityDefinitionConfig $definition)
    {
        $this->config = $definition;
    }

    /**
     * Checks if the specified additional metadata is requested.
     *
     * @param string $extraName
     *
     * @return bool
     */
    public function hasExtra($extraName)
    {
        return in_array($extraName, $this->get(self::EXTRA), true);
    }

    /**
     * Gets additional metadata if it was requested.
     *
     * @param string $extraName
     *
     * @return MetadataExtraInterface|null
     */
    public function getExtra($extraName)
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
    public function getExtras()
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
    public function setExtras(array $extras)
    {
        $names = [];
        foreach ($extras as $extra) {
            if (!$extra instanceof MetadataExtraInterface) {
                throw new \InvalidArgumentException(
                    'Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".'
                );
            }
            $names[] = $extra->getName();
            $extra->configureContext($this);
        }

        $this->extras = $extras;
        $this->set(self::EXTRA, $names);
    }

    /**
     * Gets a flag indicates whether excluded fields and associations should not be removed.
     *
     * @return bool
     */
    public function getWithExcludedProperties()
    {
        return (bool)$this->get(self::WITH_EXCLUDED_PROPERTIES);
    }

    /**
     * Sets a flag indicates whether excluded fields and associations should not be removed.
     *
     * @param bool $flag
     */
    public function setWithExcludedProperties($flag)
    {
        $this->set(self::WITH_EXCLUDED_PROPERTIES, $flag);
    }
}
