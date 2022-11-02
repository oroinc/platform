<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The strategy for retrieving security identities.
 * Unlike Symfony's implementation does not add SIDs for role hierarchy and built-in special roles.
 */
class SecurityIdentityRetrievalStrategy implements SecurityIdentityRetrievalStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = [];

        if (!$token instanceof AnonymousToken) {
            try {
                $sids[] = UserSecurityIdentity::fromToken($token);
            } catch (\InvalidArgumentException $e) {
                // ignore, user has no user security identity
            }
        }

        foreach ($token->getRoleNames() as $role) {
            $sids[] = new RoleSecurityIdentity($role);
        }

        return $sids;
    }
}
