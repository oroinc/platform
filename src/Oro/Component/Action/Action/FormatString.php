<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

class FormatString extends AbstractAction
{
    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritDoc}
     */
    protected function executeAction($context)
    {
        $result = strtr($this->getString($context), $this->getArguments($context));
        $this->contextAccessor->setValue($context, $this->options['attribute'], $result);
    }

    /**
     * Allowed options:
     *  - attribute - contains property path used to save result string
     *  - string - string used to format, first argument of strtr
     *  - arguments - array of format parameters, second argument of strtr
     *
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        if (empty($options['string'])) {
            throw new InvalidParameterException('String parameter must be specified');
        }

        if (!empty($options['arguments'])
            && !is_array($options['arguments'])
            && !$options['arguments'] instanceof PropertyPathInterface
        ) {
            throw new InvalidParameterException('Argument parameter must be either array or PropertyPathInterface');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $context
     * @return string
     */
    protected function getString($context)
    {
        return (string)$this->contextAccessor->getValue($context, $this->options['string']);
    }

    /**
     * @param mixed $context
     * @return array
     * @throws \Oro\Component\Action\Exception\InvalidParameterException
     */
    protected function getArguments($context)
    {
        $arguments = $this->getOption($this->options, 'arguments', []);
        $arguments = $this->contextAccessor->getValue($context, $arguments);

        if (!is_array($arguments) && !$arguments instanceof \Traversable) {
            throw new InvalidParameterException('Argument parameter must be traversable');
        }

        $result = [];
        foreach ($arguments as $key => $value) {
            $result['%' . $key . '%'] = $this->contextAccessor->getValue($context, $value);
        }

        return $result;
    }
}
