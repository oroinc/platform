<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

abstract class AbstractDateAction extends AbstractAction
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
        $this->contextAccessor->setValue($context, $this->options['attribute'], $this->createDateTime($context));
    }

    /**
     * @param mixed $context
     *
     * @return \DateTime
     */
    abstract protected function createDateTime($context);

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Option "attribute" name parameter is required');
        }

        if (!$options['attribute'] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Option "attribute" must be valid property definition.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function getClassOrType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
