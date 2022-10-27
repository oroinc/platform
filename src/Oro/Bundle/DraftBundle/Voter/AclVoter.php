<?php

namespace Oro\Bundle\DraftBundle\Voter;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterDecorator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Responsible for extending basic permissions to temporary entities.
 * Within draft entity, there are three permissions (VIEW, EDIT, DELETE) that do not obey the basic ACL system.
 *
 * Note that other permissions do not change! This class should not implement other logic.
 */
class AclVoter extends AclVoterDecorator
{
    private const IGNORED_PERMISSIONS = [
        BasicPermission::VIEW,
        BasicPermission::EDIT,
        BasicPermission::DELETE
    ];

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        if ($subject instanceof DraftableInterface && DraftHelper::isDraft($subject)) {
            $attributes = array_diff($attributes, self::IGNORED_PERMISSIONS);
        }

        return parent::vote($token, $subject, $attributes);
    }
}
