<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

/**
 * Verifies the validity of cron-based transition triggers.
 *
 * This verifier validates cron expressions and optional filter expressions in transition cron triggers.
 * It uses registered option verifiers to check each expression and can build query builders for filter
 * validation. It also manages the workflow and entity metadata needed for comprehensive trigger validation.
 */
class TransitionTriggerCronVerifier
{
    /** @var array */
    private $optionVerifiers = [];

    /** @var WorkflowAssembler */
    private $workflowAssembler;

    /** @var ManagerRegistry */
    private $registry;

    public function __construct(WorkflowAssembler $workflowAssembler, ManagerRegistry $registry)
    {
        $this->workflowAssembler = $workflowAssembler;
        $this->registry = $registry;
    }

    public function verify(TransitionCronTrigger $trigger)
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
     * @param TransitionCronTrigger $trigger
     * @return array
     */
    protected function prepareExpressions(TransitionCronTrigger $trigger)
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

            $options['filter'] = $this->getWorkflowItemRepository()
                ->findByStepNamesAndEntityClassQueryBuilder(
                    $steps,
                    $trigger->getEntityClass(),
                    $this->getIdentifierField($trigger->getEntityClass()),
                    $trigger->getFilter()
                )
                ->getQuery();
        }

        return $options;
    }

    /**
     * @param string $entityClass
     * @return string
     */
    protected function getIdentifierField($entityClass)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->getObjectManager($entityClass)->getClassMetadata($entityClass);

        return $metadata->getSingleIdentifierFieldName();
    }

    /**
     * @return WorkflowItemRepository
     */
    protected function getWorkflowItemRepository()
    {
        return $this->getObjectManager(WorkflowItem::class)->getRepository(WorkflowItem::class);
    }

    /**
     * @param string $entityClass
     * @return ObjectManager
     */
    protected function getObjectManager($entityClass)
    {
        return $this->registry->getManagerForClass($entityClass);
    }
}
