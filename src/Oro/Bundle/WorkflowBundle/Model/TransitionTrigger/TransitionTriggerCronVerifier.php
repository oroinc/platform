<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionTriggerCron;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class TransitionTriggerCronVerifier
{
    /** @var array */
    private $optionVerifiers = [];

    /** @var WorkflowAssembler */
    private $workflowAssembler;

    /** @var WorkflowItemRepository */
    private $workflowItemRepository;

    /**
     * @param WorkflowAssembler $workflowAssembler
     * @param WorkflowItemRepository $workflowItemRepository
     */
    public function __construct(WorkflowAssembler $workflowAssembler, WorkflowItemRepository $workflowItemRepository)
    {
        $this->workflowAssembler = $workflowAssembler;
        $this->workflowItemRepository = $workflowItemRepository;
    }

    /**
     * @param TransitionTriggerCron $trigger
     */
    public function verify(TransitionTriggerCron $trigger)
    {
        $expressions = $this->prepareExpressions($trigger);

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
     * @param TransitionTriggerCron $trigger
     * @return array
     */
    protected function prepareExpressions(TransitionTriggerCron $trigger)
    {
        $options = [];
        $options['cron'] = $trigger->getCron();
        if ($trigger->getFilter()) {
            $workflow = $this->workflowAssembler->assemble($trigger->getWorkflowDefinition(), false);
            $steps = $workflow->getStepManager()
                ->getRelatedTransitionSteps($trigger->getTransitionName())
                ->map(
                    function (Step $step) {
                        return $step->getName();
                    }
                );
            $options['filter'] = $this->workflowItemRepository->getIdsByStepNamesAndEntityClassQueryBuilder(
                $steps,
                $trigger->getWorkflowDefinition()->getRelatedEntity(),
                $trigger->getFilter()
            )->getQuery();
        }

        return $options;
    }
}
