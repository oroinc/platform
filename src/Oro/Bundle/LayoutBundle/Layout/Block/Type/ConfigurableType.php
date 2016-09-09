<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Layout\Block\OptionsConfigTrait;
use Oro\Component\Layout\Block\Type\AbstractType;

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

    /**
     * {@inheritdoc}
     */
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
