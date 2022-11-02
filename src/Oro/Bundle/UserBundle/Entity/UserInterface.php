<?php

namespace Oro\Bundle\UserBundle\Entity;

use Oro\Bundle\SecurityBundle\Model\Role;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

/**
 * Represents the interface that all user classes must implement.
 */
interface UserInterface extends SymfonyUserInterface
{
    /**
     * @param string $username New username
     *
     * @return self
     */
    public function setUsername($username): self;

    /**
     * @param string|null $password New encoded password
     *
     * @return self
     */
    public function setPassword(?string $password): self;

    /**
     * Get plain user password.
     */
    public function getPlainPassword(): ?string;

    /**
     * @param string|null $password New password as plain string
     *
     * @return self
     */
    public function setPlainPassword(?string $password): self;

    /**
     * Adds a Role to the Collection.
     */
    public function addUserRole(Role $role): self;

    /**
     * @return Role[]
     */
    public function getUserRoles(): array;
}
