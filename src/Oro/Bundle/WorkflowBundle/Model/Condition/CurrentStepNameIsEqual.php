<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Workflow condition that check that current workflow step name equals passed string
 */
class CurrentStepNameIsEqual extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'current_step_name_is_equal';

    /**
     * @var string
     */
    private $stepName;

    /**
     * @var PropertyPath
     */
    private $mainEntity;

    /** @var PropertyPath */
    private $workflowName;

    /**
     * @var WorkflowManager
     */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('step_name', $options)) {
            $this->stepName = $options['step_name'];
        } elseif (array_key_exists(0, $options)) {
            $this->stepName = $options[0];
        } else {
            throw new InvalidArgumentException('Missing "step_name" option');
        }

        if (array_key_exists('main_entity', $options)) {
            $this->mainEntity = $options['main_entity'];
        } elseif (array_key_exists(1, $options)) {
            $this->mainEntity = $options[1];
        } else {
            throw new InvalidArgumentException('Missing "main_entity" option');
        }

        if (array_key_exists('workflow', $options)) {
            $this->workflowName = $options['workflow'];
        } elseif (array_key_exists(2, $options)) {
            $this->mainEntity = $options[2];
        } else {
            throw new InvalidArgumentException('Missing "workflow" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $mainEntity = $this->resolveValue($context, $this->mainEntity, false);
        $workflowName = $this->resolveValue($context, $this->workflowName, false);

        if (!is_object($mainEntity) || null === $workflowName || null === $workflowName) {
            return false;
        }

        $workflowItem = $this->workflowManager->getWorkflowItem($mainEntity, $workflowName);
        if ($workflowItem instanceof WorkflowItem) {
            return $workflowItem->getCurrentStep()->getName() === $this->stepName;
        }

        return false;
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
    public function toArray()
    {
        return $this->convertToArray(
            [
                $this->stepName,
                $this->mainEntity,
                $this->workflowName,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode(
            [
                $this->stepName,
                $this->mainEntity,
                $this->workflowName,
            ],
            $factoryAccessor
        );
    }
}
