<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class TransitionEventTriggerExpressionVerifier implements TransitionTriggerVerifierInterface
{
    /**
     * @var TransitionEventTriggerHelper
     */
    private $eventTriggerHelper;

    /**
     * @param TransitionEventTriggerHelper $eventTriggerHelper
     */
    public function __construct(TransitionEventTriggerHelper $eventTriggerHelper)
    {
        $this->eventTriggerHelper = $eventTriggerHelper;
    }

    /** {@inheritdoc} @throws \InvalidArgumentException */
    public function verifyTrigger(BaseTransitionTrigger $trigger)
    {
        if (!$trigger instanceof TransitionEventTrigger) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unexpected type of trigger. Expected %s but got %s',
                    TransitionEventTrigger::class,
                    get_class($trigger)
                )
            );
        }

        $expression = $trigger->getRequire();

        if (null === $expression) {
            return;
        }

        $expressionLanguage = new ExpressionLanguage();
        $contextValues = $this->createContext($trigger);

        try {
            @$expressionLanguage->evaluate($expression, $contextValues);
        } catch (SyntaxError $syntaxError) {
            $message = sprintf(
                'Got syntax error: "%s" in `require: "%s"` at event trigger [%s]:%s%s in workflow %s, transition %s.',
                $syntaxError->getMessage(),
                $trigger->getRequire(),
                $trigger->getEntityClass(),
                $trigger->getEvent(),
                $trigger->getField() ? ':$' . $trigger->getField() : '',
                $trigger->getWorkflowDefinition()->getName(),
                $trigger->getTransitionName()
            );

            throw new TransitionTriggerVerifierException($message, $syntaxError);
        } catch (\RuntimeException $e) {
            return;
        }
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @return array
     */
    private function createContext(TransitionEventTrigger $trigger)
    {
        $mainEntityClass = $trigger->getWorkflowDefinition()->getRelatedEntity();

        $mainEntity = $this->createClassStub($mainEntityClass);

        $eventEntityClass = $trigger->getEntityClass();

        $eventEntity = $eventEntityClass === $mainEntityClass ? $mainEntity : $this->createClassStub($eventEntityClass);

        return $this->eventTriggerHelper->buildContextValues(
            $this->createClassStub(WorkflowDefinition::class),
            $eventEntity,
            $mainEntity,
            $this->createClassStub(WorkflowItem::class)
        );
    }

    /**
     * @param string $class
     * @return object
     */
    private function createClassStub($class)
    {
        $reflection = new \ReflectionClass($class);

        return $reflection->newInstanceWithoutConstructor();
    }
}
