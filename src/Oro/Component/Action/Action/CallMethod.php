<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Call function/method.
 *
 * Usage:
 *  - '@call_method':
 *      attribute: $.changedSkusStr
 *      method: implode
 *      method_parameters: [', ', $.changedSkus]
 *
 *  - '@call_method':
 *      attribute: $.formView
 *      object: $.form
 *      method: createView
 */
class CallMethod extends AbstractAction
{
    /**
     * @var array
     */
    protected $options;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $object = $this->getObject($context);
        $method = $this->getMethod();

        // Do not call method on null object if object is configured.
        if (!empty($this->options['object']) && !$object) {
            return;
        }

        if ($object) {
            $callback = [$object, $method];
        } else {
            $callback = $method;
        }

        $parameters = $this->getMethodParameters($context);

        $result = call_user_func_array($callback, $parameters);

        $attribute = $this->getAttribute();
        if ($attribute) {
            $this->contextAccessor->setValue($context, $attribute, $result);
        }
    }

    /**
     * @return PropertyPathInterface|null
     */
    protected function getAttribute()
    {
        return $this->getOption($this->options, 'attribute', null);
    }

    /**
     * @return string
     */
    protected function getMethod()
    {
        return $this->options['method'];
    }

    /**
     * @param mixed $context
     * @return object|null
     */
    protected function getObject($context)
    {
        return !empty($this->options['object'])
            ? $this->contextAccessor->getValue($context, $this->options['object'])
            : null;
    }

    /**
     * @param mixed $context
     * @return array
     */
    protected function getMethodParameters($context)
    {
        $parameters = $this->getOption($this->options, 'method_parameters', []);

        foreach ($parameters as $name => $value) {
            $parameters[$name] = $this->contextAccessor->getValue($context, $value);
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['method'])) {
            throw new InvalidParameterException('Method name parameter is required');
        }

        if (!empty($options['object']) && !$options['object'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Object must be valid property definition');
        }

        $this->options = $options;

        return $this;
    }
}
