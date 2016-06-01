<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;

class LimitConsumptionTimeExtension implements ExtensionInterface
{
    use ExtensionTrait;

    /**
     * @var \DateTime
     */
    protected $timeLimit;

    /**
     * @param \DateTime $timeLimit
     */
    public function __construct(\DateTime $timeLimit)
    {
        $now = new \DateTime();
        if ($timeLimit <= $now) {
            throw new \LogicException(sprintf(
                'Expected time limit is more than now, but got: now "%s", time-limit "%s"',
                $now->format(DATE_ISO8601),
                $timeLimit->format(DATE_ISO8601)
            ));
        }

        $this->timeLimit = $timeLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $this->checkTime($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->checkTime($context);
    }

    /**
     * @param Context $context
     */
    protected function checkTime(Context $context)
    {
        $now = new \DateTime();
        if ($now <= $this->timeLimit) {
            $context->getLogger()->debug(sprintf(
                '[LimitConsumptionTimeExtension] Execution interrupted as limit time has passed.'.
                ' now: "%s", time-limit: "%s"',
                $now->format(DATE_ISO8601),
                $this->timeLimit->format(DATE_ISO8601)
            ));

            $context->setExecutionInterrupted(true);
        }
    }
}
