<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;

class FormStub extends AbstractType
{
    /** @var string */
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
    public function getBlockPrefix()
    {
        return $this->name;
    }
}
