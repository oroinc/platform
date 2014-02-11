<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\ConfigurationPassInterface;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

/**
 * Class CreateRelatedEntity.
 *
 * Create workflow entity and set it to corresponding property of context
 */
class CreateRelatedEntity extends AbstractAction
{
    /**
     * @var ActionInterface
     */
    protected $createEntityAction;

    /**
     * @var ConfigurationPassInterface
     */
    protected $replacePropertyPathPass;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param ContextAccessor $contextAccessor
     * @param ActionInterface $createEntityAction
     * @param ConfigurationPassInterface $replacePropertyPathPass
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ActionInterface $createEntityAction,
        ConfigurationPassInterface $replacePropertyPathPass
    ) {
        parent::__construct($contextAccessor);

        $this->createEntityAction = $createEntityAction;
        $this->replacePropertyPathPass = $replacePropertyPathPass;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (!$context instanceof WorkflowItem) {
            throw new \InvalidArgumentException('Context must be instance of WorkflowItem');
        }

        $definition = $context->getDefinition();
        $createEntityOptions = array(
            CreateObject::OPTION_KEY_ATTRIBUTE => '$' . $definition->getEntityAttributeName(),
            CreateObject::OPTION_KEY_CLASS => $definition->getRelatedEntity(),
            CreateObject::OPTION_KEY_DATA => $this->getOption($this->options, CreateObject::OPTION_KEY_DATA)
        );
        $createEntityOptions = $this->replacePropertyPathPass->passConfiguration($createEntityOptions);

        $this->createEntityAction->initialize($createEntityOptions);
        $this->createEntityAction->execute($context);

        $entity = $this->contextAccessor->getValue($context, $createEntityOptions[CreateObject::OPTION_KEY_ATTRIBUTE]);
        $context->setEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!empty($options[CreateObject::OPTION_KEY_DATA]) && !is_array($options[CreateObject::OPTION_KEY_DATA])) {
            throw new InvalidParameterException('Object data must be an array.');
        }
        $this->options = $options;

        return $this;
    }
}
