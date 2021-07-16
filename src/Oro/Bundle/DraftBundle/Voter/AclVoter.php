<?php

namespace Oro\Bundle\DraftBundle\Voter;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter as BaseAclVoter;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Responsible for extending basic permissions to temporary entities.
 * Within draft entity, there are three permissions (VIEW, EDIT, DELETE) that do not obey the basic ACL system.
 *
 * Note that other permissions do not change! This class should not implement other logic.
 */
class AclVoter implements AclVoterInterface
{
    private const IGNORED_PERMISSIONS = [
        BasicPermission::VIEW,
        BasicPermission::EDIT,
        BasicPermission::DELETE
    ];

    /**
     * @var BaseAclVoter
     */
    private $voter;

    public function __construct(BaseAclVoter $voter)
    {
        $this->voter = $voter;
    }

    /**
     * @inheritDoc
     */
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        if ($object instanceof DraftableInterface && DraftHelper::isDraft($object)) {
            $attributes = array_diff($attributes, self::IGNORED_PERMISSIONS);
        }

        return $this->voter->vote($token, $object, $attributes);
    }

    public function addOneShotIsGrantedObserver(OneShotIsGrantedObserver $observer): void
    {
        $this->voter->addOneShotIsGrantedObserver($observer);
    }

    public function getSecurityToken(): TokenInterface
    {
        return $this->voter->getSecurityToken();
    }

    public function getAclExtension(): AclExtensionInterface
    {
        return $this->voter->getAclExtension();
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->voter->getObject();
    }

    /**
     * @param int $mask
     * @param int $accessLevel
     */
    public function setTriggeredMask($mask, $accessLevel): void
    {
        $this->voter->setTriggeredMask($mask, $accessLevel);
    }
}
