<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate that workflow transition is allowed.
 */
class TransitionIsAllowedValidator extends ConstraintValidator
{
    const ALIAS = 'oro_workflow_transition_is_allowed';

    /**
     * @var WorkflowRegistry
     */
    protected $registry;

    public function __construct(WorkflowRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Checks if current workflow item allows transition
     *
     * @param WorkflowData $value
     * @param TransitionIsAllowed $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var WorkflowItem $workflowItem */
        $workflowItem = $constraint->getWorkflowItem();
        $transitionName = $constraint->getTransitionName();
        $workflow = $this->registry->getWorkflow($workflowItem->getWorkflowName());

        $errors = new ArrayCollection();

        $result = false;
        try {
            $result = $workflow->isTransitionAllowed($workflowItem, $transitionName, $errors, true);
        } catch (InvalidTransitionException $e) {
            switch ($e->getCode()) {
                case InvalidTransitionException::UNKNOWN_TRANSITION:
                    $errors->add(
                        [
                            'message' => $constraint->unknownTransitionMessage,
                            'parameters' => ['{{ transition }}' => $transitionName],
                        ]
                    );
                    break;
                case InvalidTransitionException::NOT_START_TRANSITION:
                    $errors->add(
                        [
                            'message' => $constraint->notStartTransitionMessage,
                            'parameters' => ['{{ transition }}' => $transitionName],
                        ]
                    );
                    break;
                case InvalidTransitionException::STEP_HAS_NO_ALLOWED_TRANSITION:
                    $errors->add(
                        [
                            'message' => $constraint->stepHasNotAllowedTransitionMessage,
                            'parameters' => [
                                '{{ transition }}' => $transitionName,
                                '{{ step }}' => $workflowItem->getCurrentStep()->getName(),
                            ],
                        ]
                    );
                    break;
            }
        }

        if (!$result) {
            $this->context->addViolation($constraint->someConditionsNotMetMessage);
            if ($errors->count()) {
                foreach ($errors as $error) {
                    $this->context->addViolation($error['message'], $error['parameters'] ?? []);
                }
            }
        }
    }
}
