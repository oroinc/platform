<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

class TestCustomEntity
{
    private $user;

    private $phoneHolder;

    private $other;

    /**
     * @param TestPhoneHolder $phoneHolder
     */
    public function setPhoneHolder($phoneHolder)
    {
        $this->phoneHolder = $phoneHolder;
    }

    /**
     * @return TestPhoneHolder
     */
    public function getPhoneHolder()
    {
        return $this->phoneHolder;
    }

    /**
     * @param TestUser $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return TestUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param SomeEntity $other
     */
    public function setOther($other)
    {
        $this->other = $other;
    }

    /**
     * @return SomeEntity
     */
    public function getOther()
    {
        return $this->other;
    }
}
