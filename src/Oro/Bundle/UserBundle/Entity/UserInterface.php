<?php

namespace Oro\Bundle\UserBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

interface UserInterface extends AdvancedUserInterface
{
    /**
     * @param string $username New username
     *
     * @return UserInterface
     */
    public function setUsername($username);

    /**
     * @param string $password New encoded password
     *
     * @return UserInterface
     */
    public function setPassword($password);

    /**
     * Get plain user password.
     *
     * @return string
     */
    public function getPlainPassword();

    /**
     * @param string $password New password as plain string
     *
     * @return UserInterface
     */
    public function setPlainPassword($password);

    /**
     * Adds a Role to the Collection.
     *
     * @param RoleInterface $role
     *
     * @return UserInterface
     */
    public function addRole(RoleInterface $role);

    /**
     * Default role for UserInterface entity
     *
     * @return string
     */
    public function getDefaultRole();
}
