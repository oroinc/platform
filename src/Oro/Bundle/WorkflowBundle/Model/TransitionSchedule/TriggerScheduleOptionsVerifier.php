<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class TriggerScheduleOptionsVerifier
{
    /** @var array */
    private $optionVerifiers = [];

    /** @var WorkflowAssembler */
    private $workflowAssembler;

    /** @var TransitionQueryFactory */
    private $queryFactory;

    /** @var EntityConnector */
    private $entityConnector;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param TransitionQueryFactory $queryFactory
     * @param EntityConnector $entityConnector
     */
    public function __construct(
        WorkflowAssembler $workflowAssembler,
        TransitionQueryFactory $queryFactory,
        EntityConnector $entityConnector
    ) {
        $this->workflowAssembler = $workflowAssembler;
        $this->queryFactory = $queryFactory;
        $this->entityConnector = $entityConnector;
    }

    /**
     * @param array $options
     * @param WorkflowDefinition $workflowDefinition
     * @param string $transitionName
     */
    public function verify(array $options, WorkflowDefinition $workflowDefinition, $transitionName)
    {
        $this->validateOptions($options);

        $expressions = $this->prepareExpressions($options, $workflowDefinition, $transitionName);

        foreach ($expressions as $optionName => $value) {
            if (array_key_exists($optionName, $this->optionVerifiers)) {
                foreach ($this->optionVerifiers[$optionName] as $verifier) {
                    /** @var ExpressionVerifierInterface $verifier */
                    $verifier->verify($value);
                }
            }
        }
    }

    /**
     * @param ExpressionVerifierInterface $verifier
     * @param string $option
     */
    public function addOptionVerifier($option, ExpressionVerifierInterface $verifier)
    {
        if (!array_key_exists($option, $this->optionVerifiers)) {
            $this->optionVerifiers[$option] = [];
        }

        $this->optionVerifiers[$option][] = $verifier;
    }

    /**
     * @param array $options
     * @throws \InvalidArgumentException
     */
    private function validateOptions(array $options)
    {
        if (!isset($options['cron'])) {
            throw new \InvalidArgumentException('Option "cron" is required for transition schedule.');
        }
    }

    /**
     * @param array $options
     * @param WorkflowDefinition $workflowDefinition
     * @param string $transitionName
     * @return array
     */
    protected function prepareExpressions(array $options, WorkflowDefinition $workflowDefinition, $transitionName)
    {
        $workflow = $this->workflowAssembler->assemble($workflowDefinition, false);
        $entity = $workflowDefinition->getRelatedEntity();
        if (!$this->entityConnector->isWorkflowAware($entity)) {
            unset($options['filter']);
        }

        if (array_key_exists('filter', $options)) {
            $steps = [];
            foreach ($workflow->getStepManager()->getRelatedTransitionSteps($transitionName) as $step) {
                $steps[] = $step->getName();
            }

            $options['filter'] = $this->queryFactory->create(
                $steps,
                $entity,
                $options['filter']
            );
        }

        return $options;
    }
}
