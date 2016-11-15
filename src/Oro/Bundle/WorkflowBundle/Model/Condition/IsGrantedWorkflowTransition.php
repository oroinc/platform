<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class IsGrantedWorkflowTransition extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'is_granted_workflow_transition';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var WorkflowManager */
    protected $workflowManager;

    /** @var string */
    protected $transitionName;

    /**
     * @param SecurityFacade  $securityFacade
     * @param WorkflowManager $workflowManager
     */
    public function __construct(SecurityFacade $securityFacade, WorkflowManager $workflowManager)
    {
        $this->securityFacade = $securityFacade;
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
        $count = count($options);
        if ($count === 1) {
            $this->transitionName = reset($options);
            if (!$this->transitionName) {
                throw new InvalidArgumentException('ACL object must not be empty.');
            }
        } else {
            throw new InvalidArgumentException(
                sprintf('Options must have 1 elements, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        if (!$this->securityFacade->hasLoggedUser()) {
            return false;
        }

        $workflow = $this->workflowManager->getWorkflow($context->getWorkflowName());
        $workflowName = $workflow->getName();

        $workflowObject = sprintf(
            'workflow:%s',
            $workflowName
        );
        /**
         * TODO: Uncomment after workflow ACL extension
         */
//        if (!$this->securityFacade->isGranted('PERFORM_TRANSITIONS', $workflowObject)) {
//            //performing of transitions is forbidden on workflow level
//            return false;
//        }
//
//        $workflowTransitionObject = sprintf(
//            'workflow:%s::%s',
//            $workflowName,
//            $this->transitionName
//        );
//        if (!$this->securityFacade->isGranted('PERFORM_TRANSITION', $workflowTransitionObject)) {
//            //performing of given transition is forbidden
//            return false;
//        }

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
}
