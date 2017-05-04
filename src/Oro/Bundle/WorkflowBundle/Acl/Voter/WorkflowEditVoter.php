<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowEditVoter extends Voter
{
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === 'EDIT' && $subject instanceof WorkflowDefinition;
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
