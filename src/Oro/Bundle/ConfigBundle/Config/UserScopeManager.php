<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The manager for configuration on user level.
 */
class UserScopeManager extends AbstractScopeManager
{
    protected TokenStorageInterface $securityContext;
    protected int $scopeId = 0;

    public function setSecurityContext(TokenStorageInterface $securityContext): void
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritDoc}
     */
    public function getScopedEntityName(): string
    {
        return 'user';
    }

    /**
     * {@inheritDoc}
     */
    public function getScopeId(): int
    {
        $this->ensureScopeIdInitialized();

        return $this->scopeId;
    }

    /**
     * {@inheritDoc}
     */
    public function setScopeId(?int $scopeId): void
    {
        $this->dispatchScopeIdChangeEvent();

        $this->scopeId = $scopeId ?? 0;
    }

    /**
     * {@inheritDoc}
     */
    protected function isSupportedScopeEntity(object $entity): bool
    {
        return $entity instanceof User;
    }

    /**
     * {@inheritDoc}
     */
    protected function getScopeEntityIdValue(object $entity): int
    {
        if ($entity instanceof User) {
            return (int)$entity->getId();
        }
        throw new \LogicException(sprintf('"%s" is not supported.', ClassUtils::getClass($entity)));
    }

    /**
     * Makes sure that the scope id is set
     */
    protected function ensureScopeIdInitialized(): void
    {
        if (0 === $this->scopeId) {
            $token = $this->securityContext->getToken();
            if (null !== $token) {
                $user = $token->getUser();
                if ($user instanceof User && $user->getId()) {
                    $this->scopeId = $user->getId();
                }
            }
        }
    }
}
