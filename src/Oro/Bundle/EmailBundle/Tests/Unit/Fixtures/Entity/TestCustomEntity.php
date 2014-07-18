<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

class TestCustomEntity
{
    private $user;

    private $emailHolder;

    private $other;

    /**
     * @param TestEmailHolder $emailHolder
     */
    public function setEmailHolder($emailHolder)
    {
        $this->emailHolder = $emailHolder;
    }

    /**
     * @return TestEmailHolder
     */
    public function getEmailHolder()
    {
        return $this->emailHolder;
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
