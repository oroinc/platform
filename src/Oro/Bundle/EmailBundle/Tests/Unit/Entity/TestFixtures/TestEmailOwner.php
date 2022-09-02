<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

class TestEmailOwner implements EmailOwnerInterface
{
    protected ?int $id = null;

    protected ?string $firstName = null;

    protected ?string $lastName = null;

    protected ?OrganizationInterface $organization = null;

    protected ?string $primaryEmail = null;

    protected ?string $homeEmail = null;

    protected array $emailFields = ['primaryEmail', 'homeEmail'];

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

    public function getEmailFields()
    {
        return $this->emailFields;
    }

    public function setEmailFields(array $emailFields): void
    {
        $this->emailFields = $emailFields;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getOrganization()
    {
        return $this->organization;
    }

    public function getPrimaryEmail(): ?string
    {
        return $this->primaryEmail;
    }

    public function setPrimaryEmail(string $primaryEmail): self
    {
        $this->primaryEmail = $primaryEmail;

        return $this;
    }

    public function getHomeEmail(): ?string
    {
        return $this->homeEmail;
    }

    public function setHomeEmail(string $homeEmail): self
    {
        $this->homeEmail = $homeEmail;

        return $this;
    }
}
