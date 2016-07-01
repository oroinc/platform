<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Oro\Bundle\LayoutBundle\Layout\Block\OptionsConfigTrait;
use Oro\Component\Layout\AbstractBlockTypeExtension;

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
