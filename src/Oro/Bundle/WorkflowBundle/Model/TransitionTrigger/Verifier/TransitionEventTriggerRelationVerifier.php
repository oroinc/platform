<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;

class TransitionEventTriggerRelationVerifier implements TransitionTriggerVerifierInterface
{
    /** {@inheritdoc} @throws \InvalidArgumentException */
    public function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        if (!$trigger instanceof TransitionEventTrigger) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unexpected type of trigger. Expected %s got %s',
                    TransitionEventTrigger::class,
                    get_class($trigger)
                )
            );
        }

        $relatedEntity = $trigger->getWorkflowDefinition();

        if ($relatedEntity !== $trigger->getEntityClass() && empty($trigger->getRelation())) {
            throw new TransitionTriggerVerifierException(
                sprintf(
                    'Relation option is mandatory for non workflow related entity based event triggers. ' .
                    'Empty relation property met in `%s` workflow for `%s` transition with entity `%s` by event `%s`',
                    $trigger->getWorkflowDefinition()->getName(),
                    $trigger->getTransitionName(),
                    $trigger->getEntityClass(),
                    $trigger->getEvent()
                )
            );
        }
    }
}
