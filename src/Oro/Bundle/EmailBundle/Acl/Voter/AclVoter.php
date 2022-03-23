<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Changes the VIEW permission to VIEW_PRIVATE for private emails.
 */
class AclVoter implements AclVoterInterface
{
    private AclVoterInterface $voter;

    public function __construct(AclVoterInterface $voter)
    {
        $this->voter = $voter;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if ($subject instanceof EmailUser && $subject->isEmailPrivate()) {
            foreach ($attributes as $index => $attribute) {
                if ($attribute === BasicPermission::VIEW) {
                    $attributes[$index] = 'VIEW_PRIVATE';
                }
            }
        }

        return $this->voter->vote($token, $subject, $attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer): void
    {
        $this->voter->addOneShotIsGrantedObserver($observer);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityToken(): TokenInterface
    {
        return $this->voter->getSecurityToken();
    }

    /**
     * {@inheritDoc}
     */
    public function getAclExtension(): AclExtensionInterface
    {
        return $this->voter->getAclExtension();
    }

    /**
     * {@inheritDoc}
     */
    public function getObject()
    {
        return $this->voter->getObject();
    }

    /**
     * {@inheritDoc}
     */
    public function setTriggeredMask($mask, $accessLevel): void
    {
        $this->voter->setTriggeredMask($mask, $accessLevel);
    }
}
