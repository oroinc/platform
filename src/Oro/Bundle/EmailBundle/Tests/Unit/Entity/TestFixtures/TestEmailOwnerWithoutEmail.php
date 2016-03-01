<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

class TestEmailOwnerWithoutEmail implements EmailOwnerInterface
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /** @var OrganizationInterface */
    protected $organization = null;

    public function __construct($id = null)
    {
        $this->id = $id;
        if ($id) {
            $this->firstName = sprintf('firstName%d', $id);
            $this->lastName  = sprintf('lastName%d', $id);
        }
    }

    public function getClass()
    {
        return 'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwnerWithoutEmail';
    }

    public function getEmailFields()
    {
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getOrganization()
    {
        return $this->organization;
    }
}
