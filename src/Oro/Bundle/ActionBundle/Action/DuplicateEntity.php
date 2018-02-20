<?php

namespace Oro\Bundle\ActionBundle\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Duplicator\Duplicator;
use Oro\Component\Duplicator\DuplicatorFactory;
use Symfony\Component\PropertyAccess\PropertyPath;

class DuplicateEntity extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';
    const OPTION_KEY_TARGET = 'target';
    const OPTION_KEY_SETTINGS = 'settings';
    const OPTION_KEY_ATTRIBUTE = 'attribute';

    /**
     * @var Duplicator
     */
    protected $duplicator;

    /**
     * @var DuplicatorFactory
     */
    protected $duplicatorFactory;

    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $target = $this->getEntity($context);
        $settings = [];
        if (isset($this->options[self::OPTION_KEY_TARGET])) {
            $target = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_TARGET]);
        }
        if (isset($this->options[self::OPTION_KEY_SETTINGS])) {
            $settings = $this->getSettingsWithRealValues($context, $this->options[self::OPTION_KEY_SETTINGS]);
        }
        $copyObject = $this->getDuplicator()->duplicate($target, $settings);
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $copyObject);
    }

    /**
     * Initialize action based on passed options.
     *
     * @param array $options
     * @return ActionInterface
     * @throws InvalidParameterException
     */
    public function initialize(array $options)
    {
        if (!empty($options[self::OPTION_KEY_TARGET])) {
            $target = $options[self::OPTION_KEY_TARGET];
            if (!is_string($target) && !($target instanceof PropertyPath)) {
                throw new InvalidParameterException('Option \'target\' should be string or PropertyPath');
            }
        }
        if (!empty($options[self::OPTION_KEY_SETTINGS]) && !is_array($options[self::OPTION_KEY_SETTINGS])) {
            throw new InvalidParameterException('Option \'settings\' should be array');
        }
        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        $this->options = $options;

        return $this;
    }

    /**
     * @return Duplicator
     */
    protected function getDuplicator()
    {
        if (!$this->duplicator) {
            $this->duplicator = $this->duplicatorFactory->create();
        }

        return $this->duplicator;
    }

    /**
     * @param DuplicatorFactory $duplicatorFactory
     */
    public function setDuplicatorFactory($duplicatorFactory)
    {
        $this->duplicatorFactory = $duplicatorFactory;
    }

    /**
     * @param mixed $context
     * @return object|null
     */
    protected function getEntity($context)
    {
        if (empty($this->options[self::OPTION_KEY_ENTITY])) {
            return $context->getEntity();
        } else {
            return $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_ENTITY]);
        }
    }

    /**
     * @param mixed $context
     * @param array $settings
     *
     * @return array
     */
    protected function getSettingsWithRealValues($context, array $settings)
    {
        array_walk_recursive($settings, 'static::getValue', $context);

        return $settings;
    }

    /**
     * @param mixed $value
     * @param mixed $key
     * @param mixed $context
     */
    protected function getValue(&$value, $key, $context)
    {
        if ($value instanceof PropertyPath) {
            $value = $this->contextAccessor->getValue($context, $value);
        }
    }
}
