<?php

namespace Oro\Bundle\UserBundle\Acl\Voter;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Denies access to the anonymous role.
 */
class AnonymousRoleVoter implements VoterInterface
{
    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (!\is_object($subject) || !$subject instanceof Role) {
            return self::ACCESS_ABSTAIN;
        }

        if ($subject->getRole() === User::ROLE_ANONYMOUS) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }
}
