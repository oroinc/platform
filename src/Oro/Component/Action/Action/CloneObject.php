<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Creates a cloned object
 */
class CloneObject extends AbstractAction
{
    const OPTION_KEY_TARGET    = 'target';
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const OPTION_KEY_DATA      = 'data';
    const OPTION_KEY_IGNORE    = 'ignore';

    /**
     * @var array
     */
    protected $options;

    /**
     * @var mixed
     */
    protected $target;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $object1 = $this->cloneObject($context);
        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $object1);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_TARGET])) {
            throw new InvalidParameterException('Target parameter is required.');
        }

        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute name parameter is required.');
        }

        if (!$options[self::OPTION_KEY_ATTRIBUTE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition.');
        }

        if (!empty($options[self::OPTION_KEY_DATA]) && !is_array($options[self::OPTION_KEY_DATA])) {
            throw new InvalidParameterException('Object data must be an array.');
        }

        if (!empty($options[self::OPTION_KEY_IGNORE]) && !is_array($options[self::OPTION_KEY_IGNORE])) {
            throw new InvalidParameterException('Ignored properties should be a sequence.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param $context
     * @return object
     */
    protected function cloneObject($context)
    {
        $target = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_TARGET]);
        $object = clone $target;
        $ignoredProperties = $this->getIgnoredProperties();

        if ($ignoredProperties) {
            foreach ($ignoredProperties as $propertyName) {
                $this->contextAccessor->setValue($object, $propertyName, null);
            }
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
     * @return array
     */
    protected function getIgnoredProperties()
    {
        return $this->getOption($this->options, self::OPTION_KEY_IGNORE, []);
    }

    /**
     * @return array
     */
    protected function getObjectData()
    {
        return $this->getOption($this->options, self::OPTION_KEY_DATA, []);
    }
}
