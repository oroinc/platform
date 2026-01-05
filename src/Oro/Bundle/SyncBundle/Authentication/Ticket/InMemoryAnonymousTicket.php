<?php

namespace Oro\Bundle\SyncBundle\Authentication\Ticket;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Represents websocket anonymous user that is not stored in the database.
 */
class InMemoryAnonymousTicket implements UserInterface
{
    public function __construct(private readonly string $userIdentifier)
    {
    }

    #[\Override]
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getSalt(): string
    {
        return '';
    }

    #[\Override]
    public function eraseCredentials()
    {
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }
}
