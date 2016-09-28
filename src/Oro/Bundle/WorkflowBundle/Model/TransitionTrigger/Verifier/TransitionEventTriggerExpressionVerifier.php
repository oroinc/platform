<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class TransitionEventTriggerExpressionVerifier implements TransitionTriggerVerifierInterface
{
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
            $errorMessage = $syntaxError->getMessage();
            $message = sprintf(
                'Requirement field: "%s" - syntax error: "%s".%s',
                $trigger->getRequire(),
                $errorMessage,
                $this->retrieveDescription($errorMessage, $contextValues)
            );

            throw new TransitionTriggerVerifierException($message, $syntaxError);
        } catch (\RuntimeException $e) {
            return;
        }
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     */
    private function retrieveDescription($message, array $context)
    {
        if (preg_match('/Variable "\w+" is not valid/', $message)) {
            return sprintf(
                ' Valid context variables are: %s',
                implode(', ', $this->buildContextVarsAndTypes($context))
            );
        }

        return '';
    }

    private function buildContextVarsAndTypes(array $context)
    {
        $varsAndTypes = [];

        foreach ($context as $var => $val) {
            $varsAndTypes[] = sprintf(
                '%s [%s]',
                $var,
                get_class($val)
            );
        }

        return $varsAndTypes;
    }

    /**
     * @param TransitionEventTrigger $trigger
     * @return array
     */
    private function createContext(TransitionEventTrigger $trigger)
    {
        $definition = $trigger->getWorkflowDefinition();
        $mainEntityClass = $definition->getRelatedEntity();

        $mainEntity = $this->createClassStub($mainEntityClass);

        $eventEntityClass = $trigger->getEntityClass();

        $eventEntity = $eventEntityClass === $mainEntityClass ? $mainEntity : $this->createClassStub($eventEntityClass);

        return TransitionEventTriggerHelper::buildContextValues(
            $trigger->getWorkflowDefinition(),
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
