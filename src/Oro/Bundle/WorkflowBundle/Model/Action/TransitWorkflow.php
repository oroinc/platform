<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Action\AbstractAction as ComponentAbstractAction;
use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Performs workflow transition for given entity.
 */
class TransitWorkflow extends ComponentAbstractAction
{
    const OPTION_INDEX_ENTITY = 0;
    const OPTION_INDEX_TRANSITION = 1;
    const OPTION_INDEX_WORKFLOW = 2;
    const OPTION_INDEX_DATA = 3;
    const OPTION_INDEX_IF_ALLOWED = 4;

    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $transition;

    /**
     * @var string
     */
    protected $workflow;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var bool
     */
    private $ifAllowed = false;

    /**
     * @param ContextAccessor $contextAccessor
     * @param WorkflowManager $workflowManager
     */
    public function __construct(ContextAccessor $contextAccessor, WorkflowManager $workflowManager)
    {
        parent::__construct($contextAccessor);
        $this->workflowManager = $workflowManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $entity = $this->contextAccessor->getValue($context, $this->entity);
        $transition = $this->contextAccessor->getValue($context, $this->transition);
        $workflow = $this->contextAccessor->getValue($context, $this->workflow);
        $ifAllowed = (bool) $this->contextAccessor->getValue($context, $this->ifAllowed);
        $workflowItem = $this->workflowManager->getWorkflowItem($entity, $workflow);
        if (!$workflowItem) {
            throw new ActionException(
                sprintf(
                    'Cannot transit workflow, instance of "%s" doesn\'t have workflow item.',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        $data = $this->getData($context);
        if ($data) {
            $workflowItem->getData()->add($data);
        }

        if ($ifAllowed) {
            $this->workflowManager->transitIfAllowed($workflowItem, $transition);
        } else {
            $this->workflowManager->transit($workflowItem, $transition);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->entity = $this->getOptionValue($options, 'entity', self::OPTION_INDEX_ENTITY, true);
        $this->transition = $this->getOptionValue($options, 'transition', self::OPTION_INDEX_TRANSITION, true);
        $this->workflow = $this->getOptionValue($options, 'workflow', self::OPTION_INDEX_WORKFLOW, true);
        $this->data = (array) $this->getOptionValue($options, 'data', self::OPTION_INDEX_DATA, false);
        $this->ifAllowed = (bool) $this->getOptionValue($options, 'if_allowed', self::OPTION_INDEX_IF_ALLOWED, false);
    }

    /**
     * @param array $options
     * @param string $name
     * @param int $index
     * @param bool $required
     *
     * @return mixed|null
     */
    private function getOptionValue(array $options, string $name, int $index, bool $required)
    {
        if (isset($options[$name])) {
            return $options[$name];
        }

        if (isset($options[$index])) {
            return $options[$index];
        }

        if ($required) {
            throw new InvalidParameterException(sprintf('Option "%s" is required.', $name));
        }

        return null;
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getData($context)
    {
        $data = $this->data;

        foreach ($data as $key => $value) {
            $data[$key] = $this->contextAccessor->getValue($context, $value);
        }

        return $data;
    }
}
