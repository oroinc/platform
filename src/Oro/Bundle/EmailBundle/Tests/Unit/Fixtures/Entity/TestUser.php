<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

class TestUser implements UserInterface, EmailOwnerInterface, OrganizationAwareInterface, EmailHolderInterface
{
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

    public function getOrganization()
    {
        return $this->organization;
    }

    public function setOrganization(OrganizationInterface $organization)
    {
        $this->organization = $organization;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFirstname()
    {
        return $this->firstName;
    }

    public function getLastname()
    {
        return $this->lastName;
    }

    public function getClass()
    {
    }

    public function getEmailFields()
    {
    }

    public function getId()
    {
    }

    public function getFullname($format = '')
    {
    }

    public function getRoles()
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

    public function eraseCredentials()
    {
    }
}
