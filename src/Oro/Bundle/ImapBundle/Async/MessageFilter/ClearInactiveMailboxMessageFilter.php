<?php

namespace Oro\Bundle\ImapBundle\Async\MessageFilter;

use Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Removes duplicated messages for a 'oro.imap.clear_inactive_mailbox' MQ topic.
 */
class ClearInactiveMailboxMessageFilter implements MessageFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        if (!$buffer->hasMessagesForTopic(ClearInactiveMailboxTopic::getName())) {
            return;
        }

        $processedMessages = [];
        $messages = $buffer->getMessagesForTopic(ClearInactiveMailboxTopic::getName());
        foreach ($messages as $messageId => $message) {
            $messageKey = isset($message['id']) ? (string)$message['id'] : '_';
            if (isset($processedMessages[$messageKey])) {
                $buffer->removeMessage($messageId);
            } else {
                $processedMessages[$messageKey] = true;
            }
        }
    }
}
