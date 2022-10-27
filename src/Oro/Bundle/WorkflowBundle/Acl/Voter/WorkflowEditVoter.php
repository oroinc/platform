<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Prevents editing of active workflow definitions.
 */
class WorkflowEditVoter implements VoterInterface
{
    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (!$subject instanceof WorkflowDefinition) {
            return self::ACCESS_ABSTAIN;
        }

        $vote = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            if (BasicPermission::EDIT !== $attribute) {
                continue;
            }

            $vote = self::ACCESS_DENIED;
            if (!$subject->isActive()) {
                return self::ACCESS_GRANTED;
            }
        }

        return $vote;
    }
}
