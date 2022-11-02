<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Stub;

use Oro\Bundle\EmailBundle\Entity\EmailInterface;

class UserStub implements EmailInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $email;

    public function __construct($id, $email)
    {
        $this->id = $id;
        $this->email = $email;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailField()
    {
        return 'email';
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmailOwner()
    {
        return $this;
    }
}
