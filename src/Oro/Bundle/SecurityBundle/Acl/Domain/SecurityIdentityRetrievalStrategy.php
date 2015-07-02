<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy as BaseStrategy;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use Oro\Bundle\UserBundle\Entity\User;

class SecurityIdentityRetrievalStrategy extends BaseStrategy
{
    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $sids = parent::getSecurityIdentities($token);

        // add organization and business unit security identities
        if (!$token instanceof AnonymousToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                foreach ($user->getOrganizations() as $organization) {
                    try {
                        $sids[] = OrganizationSecurityIdentity::fromOrganization($organization);
                    } catch (\InvalidArgumentException $invalid) {
                        // ignore, user has no organization security identity
                    }
                }
                foreach ($user->getBusinessUnits() as $businessUnit) {
                    try {
                        $sids[] = BusinessUnitSecurityIdentity::fromBusinessUnit($businessUnit);
                    } catch (\InvalidArgumentException $invalid) {
                        // ignore, user has no business unit security identity
                    }
                }
            }
        }

        return $sids;
    }
}
