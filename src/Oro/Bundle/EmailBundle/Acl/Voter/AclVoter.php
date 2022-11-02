<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterDecorator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Changes the VIEW permission to VIEW_PRIVATE for private emails.
 */
class AclVoter extends AclVoterDecorator
{
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

        return parent::vote($token, $subject, $attributes);
    }
}
