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
     * {@inheritdoc}
     */
    public function getEmailField()
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmailOwner()
    {
        return $this;
    }
}
