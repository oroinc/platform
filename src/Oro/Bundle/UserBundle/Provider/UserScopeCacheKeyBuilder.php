<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ScopeBundle\Manager\ScopeCacheKeyBuilderInterface;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Adds user and organization IDs to the cache key if they exist in the current security context.
 */
class UserScopeCacheKeyBuilder implements ScopeCacheKeyBuilderInterface
{
    /** @var ScopeCacheKeyBuilderInterface */
    private $innerBuilder;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    public function __construct(ScopeCacheKeyBuilderInterface $innerBuilder, TokenStorageInterface $tokenStorage)
    {
        $this->innerBuilder = $innerBuilder;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function getCacheKey(ScopeCriteria $criteria): ?string
    {
        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return $this->innerBuilder->getCacheKey($criteria);
        }

        $userId = $this->getUserId($token);
        if (null === $userId) {
            return $this->innerBuilder->getCacheKey($criteria);
        }

        $cacheKey = 'data;user=' . $userId;
        if ($token instanceof OrganizationAwareTokenInterface) {
            $cacheKey .= ';organization=' . $token->getOrganization()->getId();
        }

        return $cacheKey;
    }

    private function getUserId(TokenInterface $token): ?string
    {
        $user = $token->getUser();

        return $user instanceof User
            ? (string)$user->getId()
            : null;
    }
}
