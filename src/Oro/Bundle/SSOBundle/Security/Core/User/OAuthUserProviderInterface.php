<?php

namespace Oro\Bundle\SSOBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;

/**
 * Represents OAuth single sign-on authentication user provider for a specific resource owner.
 */
interface OAuthUserProviderInterface
{
    /**
     * Checks if this OAuth user provider is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Gets a list of allowed domains.
     *
     * @return string[]
     */
    public function getAllowedDomains(): array;

    /**
     * Finds a user.
     */
    public function findUser(UserResponseInterface $response): ?UserInterface;
}
