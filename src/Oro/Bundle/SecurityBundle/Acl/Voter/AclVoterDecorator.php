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

    #[\Override]
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        return $this->wrapped->vote($token, $subject, $attributes);
    }

    #[\Override]
    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer): void
    {
        $this->wrapped->addOneShotIsGrantedObserver($observer);
    }

    #[\Override]
    public function getSecurityToken(): TokenInterface
    {
        return $this->wrapped->getSecurityToken();
    }

    #[\Override]
    public function getAclExtension(): AclExtensionInterface
    {
        return $this->wrapped->getAclExtension();
    }

    #[\Override]
    public function getObject()
    {
        return $this->wrapped->getObject();
    }

    #[\Override]
    public function setTriggeredMask($mask, $accessLevel): void
    {
        $this->wrapped->setTriggeredMask($mask, $accessLevel);
    }
}
