<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

class TestEmailOwner implements EmailOwnerInterface
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $firstName;

    /** @var string */
    protected $lastName;

    /** @var OrganizationInterface */
    protected $organization = null;

    public function __construct($id = null, $firstName = null)
    {
        $this->id = $id;
        if ($id) {
            $this->firstName = sprintf('firstName%d', $id);
            $this->lastName  = sprintf('lastName%d', $id);
        }
        if ($firstName) {
            $this->firstName = $firstName;
        }
    }

    public function getClass()
    {
        return 'Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\TestEmailOwner';
    }

    public function getEmailFields()
    {
        return ['primaryEmail', 'homeEmail'];
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
