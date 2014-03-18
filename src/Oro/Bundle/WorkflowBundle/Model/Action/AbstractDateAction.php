<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

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

        if (!$options['attribute'] instanceof PropertyPath) {
            throw new InvalidParameterException('Option "attribute" must be valid property definition.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @param string $value
     * @return string|void
     */
    protected function getClassOrType($value)
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
