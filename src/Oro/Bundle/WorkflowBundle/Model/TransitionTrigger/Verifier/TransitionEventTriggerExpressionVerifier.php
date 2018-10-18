<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger\Verifier;

use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\TransitionTriggerVerifierException;
use Oro\Bundle\WorkflowBundle\Helper\TransitionEventTriggerHelper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\SyntaxError;

/**
 * Trigger expression validator.
 */
class TransitionEventTriggerExpressionVerifier implements TransitionEventTriggerVerifierInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function verifyTrigger(TransitionEventTrigger $trigger)
    {
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

    /**
     * @param array $context
     * @return array
     */
    private function buildContextVarsAndTypes(array $context)
    {
        $varsAndTypes = [];

        foreach ($context as $var => $val) {
            $varsAndTypes[] = sprintf('%s [%s]', $var, $val ? get_class($val) : null);
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

        if ($eventEntityClass === $mainEntityClass) {
            $eventEntity = $mainEntity;
        } else {
            $eventEntity = $this->createClassStub($eventEntityClass);
        }

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
