<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;

class LimitConsumedMessagesExtension implements ExtensionInterface
{
    use ExtensionTrait;

    /**
     * @var int
     */
    protected $messageLimit;

    /**
     * @var int
     */
    protected $messageConsumed;

    /**
     * @param int $messageLimit
     */
    public function __construct($messageLimit)
    {
        if (false == is_int($messageLimit)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected message limit is int but got: "%s"',
                is_object($messageLimit) ? get_class($messageLimit) : gettype($messageLimit)
            ));
        }

        $this->messageLimit = $messageLimit;
        $this->messageConsumed = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        $this->checkMessageLimit($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->messageConsumed++;

        $this->checkMessageLimit($context);
    }

    /**
     * @param Context $context
     */
    protected function checkMessageLimit(Context $context)
    {
        if ($this->messageConsumed >= $this->messageLimit) {
            $context->getLogger()->debug(sprintf(
                '[LimitConsumedMessagesExtension] Message consumption is interrupted since the message limit reached.'.
                ' limit: "%s", consumed: "%s"',
                $this->messageLimit,
                $this->messageConsumed
            ));

            $context->setExecutionInterrupted(true);
        }
    }
}
