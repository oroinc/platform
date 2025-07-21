<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

class TestEmailOwnerWithoutEmail implements EmailOwnerInterface
{
    protected int $id;
    protected string $firstName;
    protected string $lastName;

    protected OrganizationInterface $organization = null;

    public function __construct($id = null)
    {
        $this->id = $id;
        if ($id) {
            $this->firstName = sprintf('firstName%d', $id);
            $this->lastName  = sprintf('lastName%d', $id);
        }
    }

    #[\Override]
    public function getEmailFields()
    {
        return null;
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    #[\Override]
    public function getFirstName()
    {
        return $this->firstName;
    }

    #[\Override]
    public function getLastName()
    {
        return $this->lastName;
    }

    public function getOrganization()
    {
        return $this->organization;
    }
}
