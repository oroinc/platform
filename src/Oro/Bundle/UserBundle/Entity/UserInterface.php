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
     *
     * @return string|null
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
     *
     * @param Role $role
     *
     * @return self
     */
    public function addUserRole(Role $role): self;

    /**
     * @return Role[]
     */
    public function getUserRoles(): array;

    /**
     * Checks whether the user is locked.
     *
     * If this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked(): bool;

    /**
     * Checks whether the user is enabled.
     *
     * If this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled(): bool;
}
