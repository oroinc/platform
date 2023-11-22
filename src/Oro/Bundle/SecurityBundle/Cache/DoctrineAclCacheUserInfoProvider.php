<?php

namespace Oro\Bundle\SecurityBundle\Cache;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Doctrine ACL query cache info provider that takes data from current token.
 */
class DoctrineAclCacheUserInfoProvider implements DoctrineAclCacheUserInfoProviderInterface
{
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentUserCacheKeyInfo(): array
    {
        if ($this->tokenAccessor->hasUser()) {
            $user = $this->tokenAccessor->getUser();
            $entityClass = ClassUtils::getClass($user);
            $entityId = $user->getId();
        } else {
            $entityClass = '';
            $entityId = '';
        }

        return [$entityClass, $entityId];
    }
}
