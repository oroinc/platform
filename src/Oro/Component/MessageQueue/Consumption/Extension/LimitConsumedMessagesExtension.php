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

        if ($messageLimit <= 0) {
            throw new \LogicException(sprintf(
                'Message limit must be more than zero but got: "%s"',
                $messageLimit
            ));
        }

        $this->messageLimit = $messageLimit;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        if (--$this->messageLimit <= 0) {
            $context->getLogger()->debug(
                '[LimitConsumedMessagesExtension] Interrupt execution as message limit exceeded'
            );

            $context->setExecutionInterrupted(true);
        }
    }
}
