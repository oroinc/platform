<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Block\Extension\Stubs;

use Oro\Component\Layout\Block\Type\AbstractType;

class CustomType extends AbstractType
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
