<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

class CreateObject extends AbstractAction
{
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const OPTION_KEY_ARGUMENTS = 'arguments';
    const OPTION_KEY_CLASS     = 'class';
    const OPTION_KEY_DATA      = 'data';

    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $object = $this->createObject($context);
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $object);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_CLASS])) {
            throw new InvalidParameterException('Class name parameter is required');
        }

        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options[self::OPTION_KEY_ATTRIBUTE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        if (!empty($options[self::OPTION_KEY_DATA]) && !is_array($options[self::OPTION_KEY_DATA])) {
            throw new InvalidParameterException('Object data must be an array.');
        }

        if (!empty($options[self::OPTION_KEY_ARGUMENTS]) && !is_array($options[self::OPTION_KEY_ARGUMENTS])) {
            throw new InvalidParameterException('Object constructor arguments must be an array.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $context
     * @return object
     */
    protected function createObject($context)
    {
        $objectClassName = $this->getObjectClassName($context);

        $arguments = $this->getConstructorArguments($context);
        if ($arguments) {
            $reflection = new \ReflectionClass($objectClassName);
            $object = $reflection->newInstanceArgs($arguments);
        } else {
            $object = new $objectClassName();
        }

        $objectData = $this->getObjectData();
        if ($objectData) {
            $this->assignObjectData($context, $object, $objectData);
        }

        return $object;
    }

    /**
     * @param mixed $context
     * @param object $entity
     * @param array $parameters
     */
    protected function assignObjectData($context, $entity, array $parameters)
    {
        foreach ($parameters as $parameterName => $valuePath) {
            $parameterValue = $this->contextAccessor->getValue($context, $valuePath);
            $this->contextAccessor->setValue($entity, $parameterName, $parameterValue);
        }
    }

    /**
     * @param mixed $context
     *
     * @return string
     */
    protected function getObjectClassName($context)
    {
        $class = $this->options[self::OPTION_KEY_CLASS];

        return $this->contextAccessor->getValue($context, $class);
    }

    /**
     * @return array
     */
    protected function getObjectData()
    {
        return $this->getOption($this->options, self::OPTION_KEY_DATA, []);
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getConstructorArguments($context)
    {
        $arguments = $this->getOption($this->options, self::OPTION_KEY_ARGUMENTS, []);
        $arguments = $this->resolveArguments($context, $arguments);

        return $arguments;
    }

    /**
     * @param mixed $context
     * @param array $arguments
     * @return array
     */
    protected function resolveArguments($context, array $arguments)
    {
        foreach ($arguments as &$argument) {
            if (is_array($argument)) {
                $argument = $this->resolveArguments($context, $argument);
            } else {
                $argument = $this->contextAccessor->getValue($context, $argument);
            }
        }

        return $arguments;
    }
}
