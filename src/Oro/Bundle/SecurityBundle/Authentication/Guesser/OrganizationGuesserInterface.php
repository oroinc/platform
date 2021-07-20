<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Guesser;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Provides an interface for classes responsible to guess an organization to login into.
 */
interface OrganizationGuesserInterface
{
    /**
     * Guesses an organization to login into.
     */
    public function guess(AbstractUser $user, TokenInterface $token = null): ?Organization;
}
