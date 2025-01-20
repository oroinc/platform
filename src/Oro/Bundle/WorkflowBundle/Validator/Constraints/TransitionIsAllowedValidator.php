<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate that workflow transition is allowed.
 */
class TransitionIsAllowedValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WorkflowRegistry $workflowRegistry
    ) {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TransitionIsAllowed) {
            throw new UnexpectedTypeException($constraint, TransitionIsAllowed::class);
        }

        if (!$value instanceof WorkflowData) {
            throw new UnexpectedTypeException($value, WorkflowData::class);
        }

        $workflowItem = $constraint->getWorkflowItem();
        $transitionName = $constraint->getTransitionName();
        $workflow = $this->workflowRegistry->getWorkflow($workflowItem->getWorkflowName());
        if (null === $workflow) {
            return;
        }

        $isTransitionAllowed = false;
        $errors = new ArrayCollection();
        try {
            $isTransitionAllowed = $workflow->isTransitionAllowed($workflowItem, $transitionName, $errors, true);
        } catch (InvalidTransitionException $e) {
            $this->handleError($e, $errors, $constraint, $workflowItem, $transitionName);
        }

        if (!$isTransitionAllowed) {
            $this->context->addViolation($constraint->someConditionsNotMetMessage);
            if ($errors->count()) {
                foreach ($errors as $error) {
                    $this->context->addViolation($error['message'], $error['parameters'] ?? []);
                }
            }
        }
    }

    private function handleError(
        InvalidTransitionException $e,
        ArrayCollection $errors,
        TransitionIsAllowed $constraint,
        WorkflowItem $workflowItem,
        string $transitionName
    ): void {
        switch ($e->getCode()) {
            case InvalidTransitionException::UNKNOWN_TRANSITION:
                $errors->add([
                    'message' => $constraint->unknownTransitionMessage,
                    'parameters' => ['{{ transition }}' => $transitionName],
                ]);
                break;
            case InvalidTransitionException::NOT_START_TRANSITION:
                $errors->add([
                    'message' => $constraint->notStartTransitionMessage,
                    'parameters' => ['{{ transition }}' => $transitionName],
                ]);
                break;
            case InvalidTransitionException::STEP_HAS_NO_ALLOWED_TRANSITION:
                $errors->add([
                    'message' => $constraint->stepHasNotAllowedTransitionMessage,
                    'parameters' => [
                        '{{ transition }}' => $transitionName,
                        '{{ step }}' => $workflowItem->getCurrentStep()?->getName()
                    ]
                ]);
                break;
        }
    }
}
