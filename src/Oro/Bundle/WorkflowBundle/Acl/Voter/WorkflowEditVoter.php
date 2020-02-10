<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Prevents editing of non active workflow definitions.
 */
class WorkflowEditVoter extends Voter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return BasicPermission::EDIT === $attribute && $subject instanceof WorkflowDefinition;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /* @var $subject WorkflowDefinition */
        return !$subject->isActive();
    }
}
