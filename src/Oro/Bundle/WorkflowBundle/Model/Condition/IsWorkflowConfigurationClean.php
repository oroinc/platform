<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

class IsWorkflowConfigurationClean extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'is_workflow_configuration_clean';

    /** @var ConfigurationChecker */
    protected $checker;

    /** @var string */
    protected $workflow;

    /**
     * @param ConfigurationChecker $checker
     */
    public function __construct(ConfigurationChecker $checker)
    {
        $this->checker = $checker;
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
        return $this->convertToArray(null);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode(null, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 !== count($options)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        if (array_key_exists('workflow', $options)) {
            $this->workflow = $options['workflow'];
        } elseif (array_key_exists(0, $options)) {
            $this->workflow = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "workflow" is required.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var WorkflowDefinition $workflow */
        $workflow = $this->resolveValue($context, $this->workflow, false);
        if (!$workflow instanceof WorkflowDefinition) {
            return true;
        }

        return $this->checker->isClean($workflow->getConfiguration());
    }
}
