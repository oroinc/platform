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

    #[\Override]
    public function getEmailField()
    {
        return 'email';
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getEmail()
    {
        return $this->email;
    }

    #[\Override]
    public function getEmailOwner()
    {
        return $this;
    }
}
