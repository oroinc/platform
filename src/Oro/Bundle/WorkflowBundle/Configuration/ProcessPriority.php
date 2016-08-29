<?php
namespace Oro\Bundle\WorkflowBundle\Configuration;

use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * The keys taken from JMS\JobQueueBundle\Entity\Job class.
 * There are constants PRIORITY_LOW, PRIORITY_DEFAULT, PRIORITY_HIGH
 */
class ProcessPriority
{
    const PRIORITY_LOW = -5;
    const PRIORITY_DEFAULT = 0;
    const PRIORITY_HIGH = 5;

    /**
     * @param int $processPriority
     *
     * @return string
     */
    public static function convertToMessageQueuePriority($processPriority)
    {
        if ($processPriority <= self::PRIORITY_LOW) {
            return MessagePriority::LOW;
        }

        if ($processPriority >= self::PRIORITY_HIGH) {
            return MessagePriority::HIGH;
        }

        return MessagePriority::NORMAL;
    }
}
