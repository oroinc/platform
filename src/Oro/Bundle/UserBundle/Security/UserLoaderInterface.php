<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Provides an interface for classes that can loads user entity from the database for the authentication system.
 */
interface UserLoaderInterface
{
    /**
     * Returns the class name of the user.
     *
     * @return string
     */
    public function getUserClass(): string;

    /**
     * Loads the user for the given login string.
     * The login string can be username, email or something else, it depends on the type of the user.
     *
     * @param string $login
     *
     * @return UserInterface|null
     */
    public function loadUser(string $login): ?UserInterface;

    /**
     * Loads the user for the given username.
     *
     * @param string $username
     *
     * @return UserInterface|null
     */
    public function loadUserByUsername(string $username): ?UserInterface;

    /**
     * Loads the user for the given email address.
     *
     * @param string $email
     *
     * @return UserInterface|null
     */
    public function loadUserByEmail(string $email): ?UserInterface;
}
