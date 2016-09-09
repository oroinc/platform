<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param TransitionQueryFactory $queryFactory
     */
    public function __construct(WorkflowAssembler $workflowAssembler, TransitionQueryFactory $queryFactory)
    {
        $this->workflowAssembler = $workflowAssembler;
        $this->queryFactory = $queryFactory;
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
        if (array_key_exists('filter', $options)) {
            $workflow = $this->workflowAssembler->assemble($workflowDefinition, false);
            
            $options['filter'] = $this->queryFactory->create(
                $workflow,
                $transitionName,
                $options['filter']
            );
        }

        return $options;
    }
}
