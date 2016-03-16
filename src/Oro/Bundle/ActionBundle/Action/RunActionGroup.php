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
    const OPTION_ACTION_GROUP   = 'action_group';
    const OPTION_PARAMETERS_MAP = 'parameters_mapping';
    const OPTION_ATTRIBUTE      = 'attribute';

    /** @var ActionGroupRegistry */
    protected $actionGroupRegistry;

    protected $parametersMapper;

    /** @var array|\Traversable */
    protected $parametersMap;

    /** @var ActionGroupExecutionArgs */
    protected $executionArguments;

    /** @var mixed */
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
        $this->parametersMap = [];
        $this->executionArguments = null;

        if (empty($options[self::OPTION_ACTION_GROUP])) {
            throw new InvalidParameterException(
                sprintf('`%s` parameter is required', self::OPTION_ACTION_GROUP)
            );
        }

        $this->executionArguments = new ActionGroupExecutionArgs($options[self::OPTION_ACTION_GROUP]);

        if (array_key_exists(self::OPTION_PARAMETERS_MAP, $options)) {
            $parametersMap = $options[self::OPTION_PARAMETERS_MAP];
            if (!is_array($parametersMap) || $parametersMap instanceof \Traversable) {
                throw new InvalidParameterException(
                    sprintf(
                        'Option `%s` must be array or implement \Traversable interface',
                        self::OPTION_PARAMETERS_MAP
                    )
                );
            }

            $this->parametersMap = $parametersMap;
        }

        $this->attribute = array_key_exists(self::OPTION_ATTRIBUTE, $options) ? $options[self::OPTION_ATTRIBUTE] : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (null === $this->executionArguments) {
            throw new \BadMethodCallException('Uninitialized action execution.');
        }

        $this->parametersMapper->mapToArgs($this->executionArguments, $this->parametersMap, $context);

        $errors = new ArrayCollection();

        $result = $this->executionArguments->execute($this->actionGroupRegistry, $errors);

        if ($this->attribute) {
            $this->contextAccessor->setValue($context, $this->attribute, $result);
        }
    }
}
