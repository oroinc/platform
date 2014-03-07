<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\ReminderBundle\Exception\SendTypeNotSupportedException;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderState;

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
     * @param Reminder $reminder
     */
    public function send(Reminder $reminder)
    {
        $state = $reminder->getState();

        if ($state->isAllSent()) {
            return;
        }

        foreach ($state->getSendTypeNames() as $sendType) {
            if (ReminderState::SEND_TYPE_SENT !== $state->getSendTypeState($sendType)) {
                $processor = $this->getProcessor($sendType);
                $processor->process($reminder);
            }
        }

        if ($state->isAllSent()) {
            $reminder->setSent(true);
            $reminder->setSentAt(new \DateTime());
        }
    }

    /**
     * @param string $sendType
     * @return SendProcessorInterface
     * @throws SendTypeNotSupportedException If processor is not supported
     */
    protected function getProcessor($sendType)
    {
        if (!isset($this->processors[$sendType])) {
            throw new SendTypeNotSupportedException(sprintf('Reminder send type "%s" is not supported.', $sendType));
        }

        return $this->processors[$sendType];
    }
}
