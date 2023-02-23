<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A filter that can be used
 * to request to add entity meta properties to the result
 * or to request to perform some additional operations.
 * @see \Oro\Bundle\ApiBundle\Filter\FilterNames::getMetaPropertyFilterName
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\AddMetaPropertyFilter
 * @see \Oro\Bundle\ApiBundle\Processor\Shared\HandleMetaPropertyFilter
 * @see \Oro\Bundle\ApiBundle\Processor\GetConfig\AddMetaProperties
 */
class MetaPropertyFilter extends StandaloneFilter implements SpecialHandlingFilterInterface
{
    /** @var array [meta property name => data type or NULL, ...] */
    private array $allowedMetaProperties = [];

    /**
     * @return array [meta property name => data type or NULL, ...]
     */
    public function getAllowedMetaProperties(): array
    {
        return $this->allowedMetaProperties;
    }

    /**
     * @param string      $name
     * @param string|null $type the data-type or NULL if the meta property represents an additional operation
     */
    public function addAllowedMetaProperty(string $name, ?string $type): void
    {
        $this->allowedMetaProperties[$name] = $type;
    }

    public function removeAllowedMetaProperty(string $name): void
    {
        unset($this->allowedMetaProperties[$name]);
    }
}
