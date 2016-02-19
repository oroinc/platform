<?php

namespace Oro\Bundle\ActionBundle\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class RunAction extends AbstractAction
{
    /** @var array */
    protected $options;

    /** @var ActionManager */
    protected $actionManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ActionManager $actionManager
     * @param ContextHelper $contextHelper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ActionManager $actionManager,
        ContextHelper $contextHelper
    ) {
        parent::__construct($contextAccessor);

        $this->actionManager = $actionManager;
        $this->contextHelper = $contextHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['action'])) {
            throw new InvalidParameterException('Action name parameter is required');
        }

        if (empty($options['entity_class'])) {
            throw new InvalidParameterException('Entity class parameter is required');
        }

        if (empty($options['entity_id'])) {
            throw new InvalidParameterException('Entity id parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $result = $this->actionManager->execute($this->options['action'], $this->getActionData($context));

        $attribute = $this->getAttribute();
        if ($attribute) {
            $this->contextAccessor->setValue($context, $attribute, $result);
        }
    }

    /**
     * @param mixed $context
     * @return ActionData
     */
    protected function getActionData($context)
    {
        $entityId = $this->contextAccessor->getValue($context, $this->options['entity_id']);
        $entityClass = $this->contextAccessor->getValue($context, $this->options['entity_class']);

        return $this->contextHelper->getActionData([
            ContextHelper::ENTITY_CLASS_PARAM => $entityClass,
            ContextHelper::ENTITY_ID_PARAM => $entityId,
        ]);
    }

    /**
     * @return PropertyPathInterface|null
     */
    protected function getAttribute()
    {
        return $this->getOption($this->options, 'attribute', null);
    }
}
