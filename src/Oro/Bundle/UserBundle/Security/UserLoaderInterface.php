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
     */
    public function getUserClass(): string;

    /**
     * Loads the user for the given login string.
     * The login string can be username, email or something else, it depends on the type of the user.
     */
    public function loadUser(string $login): ?UserInterface;

    /**
     * Loads the user for the given username.
     */
    public function loadUserByUsername(string $username): ?UserInterface;

    /**
     * Loads the user for the given email address.
     */
    public function loadUserByEmail(string $email): ?UserInterface;
}
