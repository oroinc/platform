<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\Stub;

class ClassWithToString
{
    /** @var string */
    private $representation;

    /**
     * @param string $representation
     */
    public function __construct($representation = 'string representation')
    {
        $this->representation = $representation;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->representation;
    }
}
