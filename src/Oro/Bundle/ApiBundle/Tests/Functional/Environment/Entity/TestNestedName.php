<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

class TestNestedName
{
    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /**
     * @param string|null $firstName
     * @param string|null $lastName
     */
    public function __construct($firstName = null, $lastName = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return self
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return self
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }
}
