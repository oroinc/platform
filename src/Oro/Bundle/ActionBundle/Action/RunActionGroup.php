<?php

namespace Oro\Bundle\ActionBundle\Action;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\ActionGroup;

class RunActionGroup extends AbstractAction
{
    const OPTION_ACTION_GROUP = 'action_group';
    const OPTION_PARAMETERS   = 'parameters_mapping';
    const OPTION_ATTRIBUTE    = 'attribute';

    /** @var ActionGroupRegistry */
    protected $actionGroupRegistry;

    protected $parametersMapper;

    /** @var ActionGroup */
    protected $actionGroup;

    /** @var array|\Traversable */
    protected $parameters;

    /** @var string|null */
    protected $attribute;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ActionGroupRegistry $actionGroupRegistry
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ActionGroupRegistry $actionGroupRegistry
    ) {
        parent::__construct($contextAccessor);

        $this->parametersMapper = new ActionGroup\ParametersMapper($contextAccessor);

        $this->actionGroupRegistry = $actionGroupRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        //clear up
        $this->parameters = [];
        $this->attribute = $this->actionGroup = null;

        if (empty($options[self::OPTION_ACTION_GROUP])) {
            throw new InvalidParameterException(
                sprintf('`%s` parameter is required', self::OPTION_ACTION_GROUP)
            );
        }

        $this->actionGroup = $this->actionGroupRegistry->findByName($options[self::OPTION_ACTION_GROUP]);

        if (!$this->actionGroup instanceof ActionGroup) {
            throw new \RuntimeException(
                sprintf('ActionGroup with name `%s` not found', $options[self::OPTION_ACTION_GROUP])
            );
        }

        if (array_key_exists(self::OPTION_PARAMETERS, $options)) {
            $parameters = $options[self::OPTION_PARAMETERS];
            if (!is_array($parameters) || $parameters instanceof \Traversable) {
                throw new InvalidParameterException(
                    sprintf(
                        'Option `%s` must be array or implement \Traversable interface',
                        self::OPTION_PARAMETERS
                    )
                );
            }

            $this->parameters = $parameters;
        }

        $this->attribute = array_key_exists(self::OPTION_ATTRIBUTE, $options) ? $options[self::OPTION_ATTRIBUTE] : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (null === $this->actionGroup) {
            throw new \BadMethodCallException('Uninitialized action execution.');
        }

        $arguments = new ActionGroupExecutionArgs($this->actionGroup->getDefinition()->getName());

        $this->parametersMapper->mapToArgs($arguments, $this->parameters, $context);

        $errors = new ArrayCollection();

        $result = $this->actionGroup->execute($arguments->getActionData(), $errors);

        if ($this->attribute) {
            $this->contextAccessor->setValue($context, $this->attribute, $result);
        }
    }
}
