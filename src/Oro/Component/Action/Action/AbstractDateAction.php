<?php

namespace Oro\Component\Action\Action;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Component\Action\Exception\InvalidParameterException;

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
        $this->contextAccessor->setValue($context, $this->options['attribute'], $this->createDateTime());
    }

    /**
     * @return \DateTime
     */
    abstract protected function createDateTime();

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
