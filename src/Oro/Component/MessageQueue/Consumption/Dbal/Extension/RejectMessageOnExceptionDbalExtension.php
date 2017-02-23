<?php
namespace Oro\Component\MessageQueue\Consumption\Dbal\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class RejectMessageOnExceptionDbalExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        if (! $context->getException()) {
            return;
        }

        if (! $context->getMessage()) {
            return;
        }

        $context->getMessageConsumer()->reject($context->getMessage(), true);

        $context->getLogger()->debug(
            '[RejectMessageOnExceptionDbalExtension] Execution was interrupted and message was rejected. {id}',
            ['id' => $context->getMessage()->getId()]
        );
    }
}
