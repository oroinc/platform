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

    public function getEmailField()
    {
        return 'email';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getEmailOwner()
    {
        return $this->owner;
    }
}
