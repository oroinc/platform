<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class CreateDateTime extends AbstractDateAction
{
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
        if (empty($options['time'])) {
            $options['time'] = null;
        } elseif (!is_string($options['time'])) {
            throw new InvalidParameterException(
                sprintf('Option "time" must be a string, %s given.', $this->getClassOrType($options['time']))
            );
        }

        if (empty($options['timezone'])) {
            $options['timezone'] = new \DateTimeZone('UTC');
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

        return parent::initialize($options);
    }
}
