<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    private $phone;

    private $firstName;

    private $lastName;

    public function __construct($phone = null, $firstName = null, $lastName = null)
    {
        $this->phone = $phone;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getPhone()
    {
        return $this->phone;
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
    public function eraseCredentials(): void
    {
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return '';
    }
}
