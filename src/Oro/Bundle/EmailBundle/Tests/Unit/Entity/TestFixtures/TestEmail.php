<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;

class TestEmail implements EmailInterface
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $email;

    /** @var EmailOwnerInterface */
    protected $owner;

    public function __construct($id = null, $owner = null, $email = null)
    {
        $this->id    = $id;
        $this->owner = $owner;
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
        return $this->owner;
    }
}
