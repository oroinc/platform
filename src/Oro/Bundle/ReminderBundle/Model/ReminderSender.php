<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Exception\SendTypeNotSupportedException;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Sends reminders using delegate send processors.
 */
class ReminderSender implements ReminderSenderInterface
{
    /**
     * @var SendProcessorInterface[]
     */
    protected $processors;

    /**
     * @param SendProcessorInterface[] $processors
     */
    public function __construct(array $processors)
    {
        $this->processors = array();
        foreach ($processors as $processor) {
            $this->processors[$processor->getName()] = $processor;
        }
    }

    /**
     * Handle reminder sending
     *
     * @param Reminder $reminder
     */
    public function send(Reminder $reminder)
    {
        $processor = $this->getProcessor($reminder->getMethod());
        $processor->process($reminder);

        $state = $reminder->getState();

        if (Reminder::STATE_SENT == $state) {
            $reminder->setSentAt(new \DateTime());
        }
    }

    /**
     * Get processor by send type
     *
     * @param string $method
     * @return SendProcessorInterface
     * @throws SendTypeNotSupportedException If processor is not supported
     */
    protected function getProcessor($method)
    {
        if (!isset($this->processors[$method])) {
            throw new SendTypeNotSupportedException(sprintf('Reminder method "%s" is not supported.', $method));
        }

        return $this->processors[$method];
    }
}
