<?php

namespace Oro\Bundle\ConfigBundle\Config;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * User config scope
 */
class UserScopeManager extends AbstractScopeManager
{
    /** @var TokenStorageInterface */
    protected $securityContext;

    /** @var int */
    protected $scopeId;

    /**
     * Sets the security context
     *
     * @param TokenStorageInterface $securityContext
     */
    public function setSecurityContext(TokenStorageInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        $this->ensureScopeIdInitialized();

        return $this->scopeId;
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeId($scopeId)
    {
        $this->scopeId = $scopeId;
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeIdFromEntity($entity)
    {
        if ($entity instanceof User && $entity->getId()) {
            $this->scopeId = $entity->getId();
        }
    }

    /**
     * Makes sure that the scope id is set
     */
    protected function ensureScopeIdInitialized()
    {
        if (null === $this->scopeId) {
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
