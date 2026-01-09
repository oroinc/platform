<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures;

use Symfony\Component\Security\Core\User\UserInterface;

class TestUser implements UserInterface
{
    public function getId()
    {
        return 1;
    }

    #[\Override]
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword()
    {
        return '';
    }

    public function getSalt()
    {
        return '';
    }

    public function getUsername()
    {
        return 'testUser';
    }

    #[\Override]
    public function eraseCredentials(): void
    {
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }
}
