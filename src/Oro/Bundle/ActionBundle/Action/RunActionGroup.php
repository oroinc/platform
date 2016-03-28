<?php

namespace Oro\Bundle\ActionBundle\Action;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\OptionsResolverTrait;

use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupExecutionArgs;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;

class RunActionGroup extends AbstractAction
{
    use OptionsResolverTrait;

    const OPTION_ACTION_GROUP   = 'action_group';
    const OPTION_PARAMETERS_MAP = 'parameters_mapping';
    const OPTION_RESULTS        = 'results';
    const OPTION_RESULT         = 'result';
    const ERRORS_DEFAULT_KEY    = 'errors';

    /** @var ActionGroupRegistry */
    protected $actionGroupRegistry;

    /** @var ActionGroup\PropertyMapper */
    protected $propertyMapper;

    /** @var ActionGroupExecutionArgs */
    private $executionArgs;

    /** @var array */
    private $options;

    /** @var PropertyPathInterface */
    private $errorsPath;

    /**
     * @param ActionGroupRegistry $actionGroupRegistry
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(ActionGroupRegistry $actionGroupRegistry, ContextAccessor $contextAccessor)
    {
        parent::__construct($contextAccessor);

        $this->propertyMapper = new ActionGroup\PropertyMapper($contextAccessor);

        $this->actionGroupRegistry = $actionGroupRegistry;

        $this->errorsPath = new PropertyPath(self::ERRORS_DEFAULT_KEY);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::OPTION_ACTION_GROUP);
        $resolver->setAllowedTypes(self::OPTION_ACTION_GROUP, 'string');
        $resolver->setAllowedValues(self::OPTION_ACTION_GROUP, $this->actionGroupRegistry->getNames());

        $resolver->setDefined(
            [
                self::OPTION_PARAMETERS_MAP,
                self::OPTION_RESULTS,
                self::OPTION_RESULT
            ]
        );

        $resolver->setAllowedTypes(
            self::OPTION_PARAMETERS_MAP,
            ['array']
        );

        $resolver->setAllowedValues(
            self::OPTION_RESULTS,
            function ($value) {
                foreach ($value as $target => $source) {
                    if ((!is_string($target) && !$target instanceof PropertyPathInterface) ||
                        (!$source instanceof PropertyPathInterface)
                    ) {
                        return false;
                    }
                }

                return true;
            }
        );

        $resolver->setAllowedTypes(
            self::OPTION_RESULT,
            ['null', 'Symfony\Component\PropertyAccess\PropertyPathInterface']
        );

        $resolver->setAllowedTypes(self::OPTION_RESULTS, ['array']);

        $resolver->setDefaults(
            [
                self::OPTION_PARAMETERS_MAP => [],
                self::OPTION_RESULTS => [],
                self::OPTION_RESULT => null
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->options = $this->resolve($options);

        $this->executionArgs = new ActionGroupExecutionArgs($this->options[self::OPTION_ACTION_GROUP]);

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

        $this->propertyMapper->toArgs($this->executionArgs, $this->options[self::OPTION_PARAMETERS_MAP], $context);

        $errors = $this->contextAccessor->getValue($context, $this->errorsPath) ?: null;
        $result = $this->executionArgs->execute($this->actionGroupRegistry, $errors);

        //set results through attributes map if any
        if (0 !== count($this->options[self::OPTION_RESULTS])) {
            $this->propertyMapper->transfer($result, $this->options[self::OPTION_RESULTS], $context);
        }

        //set result through single property path if exists
        if (null !== $this->options[self::OPTION_RESULT]) {
            $this->contextAccessor->setValue($context, $this->options[self::OPTION_RESULT], $result);
        }
    }
}
