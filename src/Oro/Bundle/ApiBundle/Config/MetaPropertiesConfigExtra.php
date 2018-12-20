<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request to add entity meta properties to entity configuration.
 */
class MetaPropertiesConfigExtra implements ConfigExtraInterface
{
    const NAME = 'meta_properties';

    /** @var array[] [property name => data type, ...] */
    private $metaProperties = [];

    /**
     * Gets names of all meta properties.
     *
     * @return string[]
     */
    public function getMetaPropertyNames()
    {
        return array_keys($this->metaProperties);
    }

    /**
     * Gets the type of a meta property.
     *
     * @param string $name
     *
     * @return string
     */
    public function getTypeOfMetaProperty($name)
    {
        $this->assertMetaPropertyExists($name);

        return $this->metaProperties[$name];
    }

    /**
     * Sets the type of a meta property.
     *
     * @param string $name
     * @param string $dataType
     */
    public function setTypeOfMetaProperty($name, $dataType)
    {
        $this->assertMetaPropertyExists($name);

        $this->metaProperties[$name] = $dataType;
    }

    /**
     * Adds a meta property.
     *
     * @param string $name
     * @param string $dataType
     */
    public function addMetaProperty($name, $dataType = 'string')
    {
        $this->metaProperties[$name] = $dataType;
    }

    /**
     * Removes a meta property.
     *
     * @param string $name
     */
    public function removeMetaProperty($name)
    {
        unset($this->metaProperties[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
        $context->set(self::NAME, $this->metaProperties);
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return 'meta_properties:' . implode(',', array_keys($this->metaProperties));
    }

    /**
     * @param string $name
     */
    private function assertMetaPropertyExists($name)
    {
        if (!array_key_exists($name, $this->metaProperties)) {
            throw new \InvalidArgumentException(
                sprintf('The "%s" meta property does not exist.', $name)
            );
        }
    }
}
