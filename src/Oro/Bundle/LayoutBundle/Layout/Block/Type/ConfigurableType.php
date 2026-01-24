<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\OptionsConfigTrait;
use Oro\Component\Layout\Block\Type\AbstractType;

/**
 * A dynamically configurable block type with runtime-defined name and parent.
 *
 * This block type allows the name and parent type to be set at runtime through
 * setter methods, enabling programmatic creation of block types without requiring
 * separate class definitions. It integrates with {@see OptionsConfigTrait} for automatic
 * options handling.
 */
class ConfigurableType extends AbstractType
{
    use OptionsConfigTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $parent;

    /**
     * @return mixed
     */
    #[\Override]
    public function getName()
    {
        if ($this->name === null) {
            throw new \LogicException('Name of block type does not configured');
        }
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException(
                sprintf('Name of block type should be a string, %s given', gettype($name))
            );
        }
        $this->name = $name;
        return $this;
    }

    #[\Override]
    public function getParent()
    {
        return $this->parent ?: parent::getParent();
    }

    /**
     * @param string $parent
     * @return mixed
     */
    public function setParent($parent)
    {
        if (!is_string($parent)) {
            throw new \InvalidArgumentException(
                sprintf('Name of parent block type should be a string, %s given', gettype($parent))
            );
        }
        $this->parent = $parent;
        return $this;
    }
}
