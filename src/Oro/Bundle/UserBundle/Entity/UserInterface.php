<?php


namespace Oro\Bundle\UserBundle\Entity;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

interface UserInterface extends AdvancedUserInterface
{
    /**
     * @param  string $username New username
     *
     * @return User
     */
    public function setUsername($username);

    /**
     * @param string $password New encoded password
     *
     * @return User
     */
    public function setPassword($password);

    /**
     * Get plain user password.
     *
     * @return string
     */
    public function getPlainPassword();

    /**
     * @param  string $password New password as plain string
     *
     * @return User
     */
    public function setPlainPassword($password);

    /**
     * Adds a Role to the Collection.
     *
     * @param  Role $role
     *
     * @return User
     */
    public function addRole(Role $role);
}
