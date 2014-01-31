<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class CreateDateTime extends AbstractAction
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
    protected function createDateTime()
    {
        return new \DateTime(
            $this->getOption($this->options, 'time'),
            $this->getOption($this->options, 'timezone')
        );
    }

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

        if (empty($options['time'])) {
            $options['time'] = null;
        } elseif (!is_string($options['time'])) {
            throw new InvalidParameterException(
                sprintf('Option "time" must be a string, %s given.', $this->getClassOrType($options['time']))
            );
        }

        if (empty($options['timezone'])) {
            $options['timezone'] = null;
        } elseif (is_string($options['timezone'])) {
            $options['timezone'] = new \DateTimeZone($options['timezone']);
        } elseif (!$options['timezone'] instanceof \DateTimeZone) {
            throw new InvalidParameterException(
                sprintf(
                    'Option "timezone" must be a string or instance of DateTimeZone, %s given.',
                    $this->getClassOrType($options['timezone'])
                )
            );
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
