<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\TransitionScheduleHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class TriggerScheduleOptionsVerifier
{
    /** @var array */
    private $optionVerifiers = [];

    /** @var WorkflowAssembler */
    private $workflowAssembler;

    /** @var TransitionScheduleHelper */
    private $transitionScheduleHelper;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param TransitionScheduleHelper $transitionScheduleHelper
     */
    public function __construct(
        WorkflowAssembler $workflowAssembler,
        TransitionScheduleHelper $transitionScheduleHelper
    ) {
        $this->workflowAssembler = $workflowAssembler;
        $this->transitionScheduleHelper = $transitionScheduleHelper;
    }

    /**
     * @param array $options
     * @param WorkflowDefinition $workflowDefinition
     * @param string $transitionName
     */
    public function verify(array $options, WorkflowDefinition $workflowDefinition, $transitionName)
    {
        $this->validateOptions($options);

        foreach ($this->prepareExpressions($options, $workflowDefinition, $transitionName) as $optionName => $value) {
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
            throw new \InvalidArgumentException(
                'Option "cron" is REQUIRED for transition schedule.'
            );
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

            $steps = [];
            foreach ($workflow->getStepManager()->getRelatedTransitionSteps($transitionName) as $step) {
                $steps[] = $step->getName();
            }

            $query = $this->transitionScheduleHelper->createQuery(
                $steps,
                $workflowDefinition->getRelatedEntity(),
                $options['filter']
            );

            $options['filter'] = $query->getDQL();
        }

        return $options;
    }
}
