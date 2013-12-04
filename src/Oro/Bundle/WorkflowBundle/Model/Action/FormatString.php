<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

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
        $result = vsprintf($this->getString($context), $this->getArguments($context));
        $this->contextAccessor->setValue($context, $this->options['attribute'], $result);
    }

    /**
     * Allowed options:
     *  - attribute - contains property path used to save result string
     *  - string - string used to format, first argument of vsprintf
     *  - arguments - array of format parameters, second argument of vsprintf
     *
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }
        if (!$options['attribute'] instanceof PropertyPath) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        if (empty($options['string'])) {
            throw new InvalidParameterException('String parameter must be specified');
        }

        if (!empty($options['arguments'])
            && !is_array($options['arguments'])
            && !$options['arguments'] instanceof PropertyPath
        ) {
            throw new InvalidParameterException('Argument parameter must be either array or PropertyPath');
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
     * @throws \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     */
    protected function getArguments($context)
    {
        $arguments = $this->getOption($this->options, 'arguments', array());
        $arguments = $this->contextAccessor->getValue($context, $arguments);

        if (!is_array($arguments) && !$arguments instanceof \Traversable) {
            throw new InvalidParameterException('Argument parameter must be traversable');
        }

        $result = array();
        foreach ($arguments as $value) {
            $result[] = $this->contextAccessor->getValue($context, $value);
        }

        return $result;
    }
}
