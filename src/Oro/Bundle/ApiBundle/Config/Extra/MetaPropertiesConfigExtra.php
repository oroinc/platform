<?php

namespace Oro\Bundle\ApiBundle\Config\Extra;

use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

/**
 * An instance of this class can be added to the config extras of the context
 * to request to add entity meta properties to entity configuration
 * or to request to perform some additional operations (in this case the data-type of a meta property must be NULL).
 * @see \Oro\Bundle\ApiBundle\Filter\MetaPropertyFilter
 */
class MetaPropertiesConfigExtra implements ConfigExtraInterface
{
    public const NAME = 'meta_properties';

    /** @var array[] [property name => data type, ...] */
    private array $metaProperties = [];

    /**
     * Gets names of all meta properties.
     *
     * @return string[]
     */
    public function getMetaPropertyNames(): array
    {
        return array_keys($this->metaProperties);
    }

    /**
     * Gets the data-type of a meta property.
     *
     * @param string $name
     *
     * @return string|null the data-type or NULL if the given meta property should not be added to entity config
     */
    public function getTypeOfMetaProperty(string $name): ?string
    {
        $this->assertMetaPropertyExists($name);

        return $this->metaProperties[$name];
    }

    /**
     * Sets the type of a meta property.
     *
     * @param string      $name
     * @param string|null $type the data-type or NULL if the meta property should not be added to entity config
     */
    public function setTypeOfMetaProperty(string $name, ?string $type): void
    {
        $this->assertMetaPropertyExists($name);

        $this->metaProperties[$name] = $type;
    }

    /**
     * Adds a meta property.
     *
     * @param string      $name
     * @param string|null $type the data-type or NULL if the meta property should not be added to entity config
     */
    public function addMetaProperty(string $name, ?string $type): void
    {
        $this->metaProperties[$name] = $type;
    }

    /**
     * Removes a meta property.
     */
    public function removeMetaProperty(string $name): void
    {
        unset($this->metaProperties[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context): void
    {
        $context->set(self::NAME, $this->metaProperties);
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart(): ?string
    {
        return 'meta_properties:' . implode(',', array_keys($this->metaProperties));
    }

    private function assertMetaPropertyExists(string $name): void
    {
        if (!\array_key_exists($name, $this->metaProperties)) {
            throw new \InvalidArgumentException(sprintf('The "%s" meta property does not exist.', $name));
        }
    }
}
