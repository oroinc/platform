<?php

namespace Oro\Bundle\ActionBundle\Action;

use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;

class RunActionGroup extends AbstractAction
{
    const OPTION_ACTION_GROUP   = 'action_group';
    const OPTION_PARAMETERS_MAP = 'parameters_mapping';
    const OPTION_ATTRIBUTE      = 'attribute';

    /** @var ActionGroupRegistry */
    protected $actionGroupRegistry;

    /** @var ActionGroup\ParametersMapper */
    protected $parametersMapper;

    /** @var array|\Traversable */
    protected $parametersMap;

    /** @var ActionGroupExecutionArgs */
    protected $executionArgs;

    /** @var string|PropertyPathInterface */
    protected $attribute;

    /** @var PropertyPathInterface */
    protected $errors;

    /**
     * @param ActionGroupRegistry $actionGroupRegistry
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(ActionGroupRegistry $actionGroupRegistry, ContextAccessor $contextAccessor)
    {
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
        $this->executionArgs = null;

        if (empty($options[self::OPTION_ACTION_GROUP])) {
            throw new InvalidParameterException(
                sprintf('`%s` parameter is required', self::OPTION_ACTION_GROUP)
            );
        }

        $this->executionArgs = new ActionGroupExecutionArgs($options[self::OPTION_ACTION_GROUP]);

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

        $this->attribute = $this->getOption($options, self::OPTION_ATTRIBUTE);
        $this->errors = new PropertyPath('errors');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (null === $this->executionArgs) {
            throw new \BadMethodCallException('Uninitialized action execution.');
        }

        $this->parametersMapper->mapToArgs($this->executionArgs, $this->parametersMap, $context);

        $errors = $this->contextAccessor->getValue($context, $this->errors) ?: null;
        $result = $this->executionArgs->execute($this->actionGroupRegistry, $errors);

        if ($this->attribute) {
            $this->contextAccessor->setValue($context, $this->attribute, $result);
        }

        $this->transferValue($context, $result, 'redirectUrl');
        $this->transferValue($context, $result, 'refreshGrid');
    }

    /**
     * @param ActionData $to
     * @param ActionData $from
     * @param string $property
     */
    protected function transferValue($to, $from, $property)
    {
        $property = new PropertyPath($property);

        $origValue = $this->contextAccessor->getValue($to, $property);
        $newValue = $this->contextAccessor->getValue($from, $property);

        if (is_array($origValue) || is_array($newValue)) {
            $newValue = array_merge((array)$origValue, (array)$newValue);
        }

        if (null !== $newValue) {
            $this->contextAccessor->setValue($to, $property, $newValue);
        }
    }
}
