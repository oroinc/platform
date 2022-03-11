<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * User config scope
 */
class UserScopeManager extends AbstractScopeManager
{
    protected TokenStorageInterface $securityContext;
    protected ?int $scopeId = null;

    /**
     * Sets the security context
     */
    public function setSecurityContext(TokenStorageInterface $securityContext): void
    {
        $this->securityContext = $securityContext;
    }

    public function getScopedEntityName(): string
    {
        return 'user';
    }

    public function getScopeId(): ?int
    {
        $this->ensureScopeIdInitialized();

        return $this->scopeId;
    }

    public function setScopeId(int $scopeId): void
    {
        $this->dispatchScopeIdChangeEvent();

        $this->scopeId = $scopeId;
    }

    protected function isSupportedScopeEntity($entity): bool
    {
        return $entity instanceof User;
    }

    protected function getScopeEntityIdValue($entity): mixed
    {
        if ($entity instanceof User) {
            return $entity->getId();
        }
        throw new \LogicException(sprintf('"%s" is not supported.', \get_class($entity)));
    }

    /**
     * Makes sure that the scope id is set
     */
    protected function ensureScopeIdInitialized(): void
    {
        if (!$this->scopeId) {
            $scopeId = 0;

            $token = $this->securityContext->getToken();
            if ($token) {
                $user = $token->getUser();
                if ($user instanceof User && $user->getId()) {
                    $scopeId = $user->getId();
                }
            }

            $this->scopeId = $scopeId;
        }
    }
}
