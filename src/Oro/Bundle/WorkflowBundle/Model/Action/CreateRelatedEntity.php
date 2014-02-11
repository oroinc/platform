<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ConfigurationPass\ConfigurationPassInterface;

/**
 * Class CreateRelatedEntity.
 *
 * Create workflow entity and set it to corresponding property of context
 */
class CreateRelatedEntity extends AbstractAction
{
    const OPTION_KEY_DATA = 'data';
    const OPTION_KEY_CLASS = 'class';
    const OPTION_KEY_ATTRIBUTE = 'attribute';

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
     * @param ActionInterface $createEntityAction
     * @param ConfigurationPassInterface $replacePropertyPathPass
     */
    public function __construct(
        ActionInterface $createEntityAction,
        ConfigurationPassInterface $replacePropertyPathPass
    ) {
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
            self::OPTION_KEY_ATTRIBUTE => '$' . $definition->getEntityAttributeName(),
            self::OPTION_KEY_CLASS => $definition->getRelatedEntity(),
            self::OPTION_KEY_DATA => $this->getOption($this->options, self::OPTION_KEY_DATA)
        );
        $createEntityOptions = $this->replacePropertyPathPass->passConfiguration($createEntityOptions);

        $this->createEntityAction->initialize($createEntityOptions);
        $entity = $this->createEntityAction->execute($context);
        $context->setEntity($entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!empty($options[self::OPTION_KEY_DATA]) && !is_array($options[self::OPTION_KEY_DATA])) {
            throw new InvalidParameterException('Object data must be an array.');
        }
        $this->options = $options;

        return $this;
    }
}
