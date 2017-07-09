<?php

namespace Oro\Component\Action\Action;

use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class CreateDateTime extends AbstractDateAction
{
    /**
     * @param $context
     *
     * @return \DateTime
     */
    protected function createDateTime($context)
    {
        return new \DateTime(
            $this->getOption($this->options, 'time'),
            $this->getTimeZone($context)
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
        } elseif (!$options['timezone'] instanceof PropertyPathInterface
            && !is_string($options['timezone'])
            && !$options['timezone'] instanceof \DateTimeZone
        ) {
            throw new InvalidParameterException(
                sprintf(
                    'Option "timezone" must be a PropertyPath or string or instance of DateTimeZone, %s given.',
                    $this->getClassOrType($options['timezone'])
                )
            );
        }

        return parent::initialize($options);
    }


    /**
     * @param mixed $context
     *
     * @return \DateTimeZone|string
     */
    protected function getTimeZone($context)
    {
        $timeZone = $this->contextAccessor->getValue($context, $this->options['timezone']);
        if (is_string($timeZone)) {
            $timeZone = new \DateTimeZone($timeZone);
        }

        return $timeZone;
    }
}
