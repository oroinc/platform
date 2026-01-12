<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;

/**
 * A stub form type for testing purposes.
 *
 * This minimal form type implementation is useful in unit tests where a
 * concrete form type is needed but the actual form behavior is not relevant
 * to the test being performed.
 */
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
    #[\Override]
    public function getBlockPrefix(): string
    {
        return $this->name;
    }
}
