<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface, EmailOwnerInterface, OrganizationAwareInterface, EmailHolderInterface
{
    private $id;

    private $email;

    private $firstName;

    private $lastName;

    private $organization;

    public function __construct($email = null, $firstName = null, $lastName = null, $organization = null)
    {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->organization = $organization;
    }

    #[\Override]
    public function getOrganization()
    {
        return $this->organization;
    }

    #[\Override]
    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;
    }

    #[\Override]
    public function getEmail()
    {
        return $this->email;
    }

    #[\Override]
    public function getFirstname()
    {
        return $this->firstName;
    }

    #[\Override]
    public function getLastname()
    {
        return $this->lastName;
    }

    #[\Override]
    public function getEmailFields()
    {
    }

    #[\Override]
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getFullname($format = '')
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    #[\Override]
    public function getRoles(): array
    {
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
    }

    #[\Override]
    public function eraseCredentials()
    {
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return '';
    }
}
