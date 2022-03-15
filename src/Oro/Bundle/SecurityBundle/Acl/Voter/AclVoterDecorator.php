<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The base class for AclVoter decorators.
 */
abstract class AclVoterDecorator implements AclVoterInterface
{
    private AclVoterInterface $wrapped;

    public function __construct(AclVoterInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        return $this->wrapped->vote($token, $subject, $attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer): void
    {
        $this->wrapped->addOneShotIsGrantedObserver($observer);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityToken(): TokenInterface
    {
        return $this->wrapped->getSecurityToken();
    }

    /**
     * {@inheritDoc}
     */
    public function getAclExtension(): AclExtensionInterface
    {
        return $this->wrapped->getAclExtension();
    }

    /**
     * {@inheritDoc}
     */
    public function getObject()
    {
        return $this->wrapped->getObject();
    }

    /**
     * {@inheritDoc}
     */
    public function setTriggeredMask($mask, $accessLevel): void
    {
        $this->wrapped->setTriggeredMask($mask, $accessLevel);
    }
}
