<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

class TestNestedValue
{
    /** @var string */
    private $value;

    /**
     * @param string|null $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
