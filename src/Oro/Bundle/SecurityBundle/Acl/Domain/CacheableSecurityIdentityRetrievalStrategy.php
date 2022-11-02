<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The strategy that use a local cache to increase performance of SIDs retrieval
 * in case if there are a lot of ACL checks during request.
 */
class CacheableSecurityIdentityRetrievalStrategy implements SecurityIdentityRetrievalStrategyInterface
{
    /** @var SecurityIdentityRetrievalStrategyInterface */
    private $innerStrategy;

    /** @var array [user name => [SID, ...], ...] */
    private $sids = [];

    public function __construct(SecurityIdentityRetrievalStrategyInterface $innerStrategy)
    {
        $this->innerStrategy = $innerStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityIdentities(TokenInterface $token)
    {
        $username = $token->getUsername();
        if (isset($this->sids[$username])) {
            return $this->sids[$username];
        }

        $sids = $this->innerStrategy->getSecurityIdentities($token);
        $this->sids[$username] = $sids;

        return $sids;
    }
}
