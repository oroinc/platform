<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\SecurityBundle\Acl\Domain\DomainObjectWrapper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Used to perform ACL check for ability to perform transition
 *  - all transitions check (PERFORM_TRANSITIONS)
 *  - specific transition check (PERFORM_TRANSITION)
 */
class IsGrantedWorkflowTransition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'is_granted_workflow_transition';

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var string */
    protected $transitionName;

    /** @var string */
    protected $targetStepName;

    /** @var WorkflowManager */
    protected $workflowManager;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param WorkflowManager $workflowManager
     */
    public function setWorkflowManager(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (2 === count($options)) {
            list($this->transitionName, $this->targetStepName) = $options;
            if (!$this->transitionName) {
                throw new InvalidArgumentException('Transition name must not be empty.');
            }
            if (!$this->targetStepName) {
                throw new InvalidArgumentException('Target step name must not be empty.');
            }
        } else {
            throw new InvalidArgumentException(
                sprintf('Options must have 2 elements, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        if (!$this->tokenAccessor->hasUser()) {
            return true;
        }

        $objectWrapper = $this->getDomainObjectWrapper($context);
        if (!$this->authorizationChecker->isGranted('PERFORM_TRANSITIONS', $objectWrapper)) {
            //performing of transitions is forbidden on workflow level
            return false;
        }

        if (!$this->authorizationChecker->isGranted(
            'PERFORM_TRANSITION',
            new FieldVote(
                $objectWrapper,
                sprintf(
                    '%s|%s|%s',
                    $this->transitionName,
                    $this->getCurrentStepName($context),
                    $this->targetStepName
                )
            )
        )) {
            //performing of given transition is forbidden
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->transitionName]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->transitionName], $factoryAccessor);
    }

    /**
     * @param WorkflowItem $context
     *
     * @return string|null
     */
    protected function getCurrentStepName(WorkflowItem $context)
    {
        $currentStep = $context->getCurrentStep();
        if (null !== $currentStep) {
            return $currentStep->getName();
        }

        return null;
    }

    /**
     * @param WorkflowItem $context
     * @return DomainObjectWrapper
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    protected function getDomainObjectWrapper(WorkflowItem $context): DomainObjectWrapper
    {
        $entity = $context->getEntity();
        if ($entity && !$context->getEntityId()) {
            $workflow = $this->workflowManager->getWorkflow($context);
            $transition = $workflow->getTransitionManager()->getTransition($this->transitionName);
            if ($transition && $transition->isStart()) {
                $entity = 'workflow:' . $context->getWorkflowName();
            }
        }
        return new DomainObjectWrapper(
            $entity,
            new ObjectIdentity('workflow', $context->getWorkflowName())
        );
    }
}
