<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * This extension signals (by calling the tick method) that a consumer did not fail and continue to work normally
 * before the next message is taken into the processing.
 */
class ConsumerHeartbeatExtension extends AbstractExtension
{
    /** @var int */
    private $updateHeartbeatPeriod;

    /** @var ConsumerHeartbeat */
    private $consumerHeartbeat;

    /** @var \DateTime */
    private $lastUpdatedTime;

    /**
     * @param integer           $updateHeartbeatPeriod
     * @param ConsumerHeartbeat $consumerHeartbeat
     */
    public function __construct($updateHeartbeatPeriod, ConsumerHeartbeat $consumerHeartbeat)
    {
        $this->updateHeartbeatPeriod = $updateHeartbeatPeriod;
        $this->consumerHeartbeat = $consumerHeartbeat;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        // do nothing if the check was disabled with 0 config option value
        if ($this->updateHeartbeatPeriod === 0) {
            return;
        }

        $currentTime = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!$this->lastUpdatedTime
            || (($currentTime->getTimestamp() - $this->lastUpdatedTime->getTimestamp())/60
                >= $this->updateHeartbeatPeriod
            )
        ) {
            $context->getLogger()->info('Update the consumer state time.');
            $this->consumerHeartbeat->tick();
            $this->lastUpdatedTime = $currentTime;
        }
    }
}
