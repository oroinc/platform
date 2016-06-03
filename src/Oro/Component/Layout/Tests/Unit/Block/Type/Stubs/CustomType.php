<?php

namespace Oro\Component\Layout\Tests\Unit\Block\Type\Stubs;

use Oro\Component\Layout\Block\Type\AbstractType;

class CustomType extends AbstractType
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     * @return CustomType
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param array $options
     * @return CustomType
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
