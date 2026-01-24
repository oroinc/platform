<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\Layout\Block\OptionsConfigTrait;
use Oro\Component\Layout\AbstractBlockTypeExtension;

/**
 * Extends block types with configurable options and automatic view building.
 *
 * This extension allows block type implementations to define options configuration
 * declaratively through the {@see OptionsConfigTrait}, automatically handling option
 * resolution and view variable assignment without requiring explicit method overrides.
 */
class ConfigurableTypeExtension extends AbstractBlockTypeExtension
{
    use OptionsConfigTrait;

    /**
     * @var string
     */
    protected $extendedType;

    /**
     * @return mixed
     */
    #[\Override]
    public function getExtendedType()
    {
        if ($this->extendedType === null) {
            throw new \LogicException('Name of extended type should be provided for block type extension');
        }
        return $this->extendedType;
    }

    /**
     * @param string $extendedType
     * @return $this
     */
    public function setExtendedType($extendedType)
    {
        if (!is_string($extendedType)) {
            throw new \InvalidArgumentException(
                sprintf('Name of extended type should be a string, %s given', gettype($extendedType))
            );
        }
        $this->extendedType = $extendedType;
        return $this;
    }
}
