<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Layout\DataProvider\Stubs;

use Symfony\Component\Form\AbstractType;

class LayoutFormStub extends AbstractType
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return $this->name;
    }
}
