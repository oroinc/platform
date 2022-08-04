<?php

namespace Oro\Bundle\TranslationBundle\Async;

use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;

/**
 * Removes duplicated messages for "oro.translation.dump_js_translations" topic.
 */
class DumpJsTranslationsMessageFilter implements MessageFilterInterface
{
    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        $hasDumpJsTranslationsMessage = false;
        $messages = $buffer->getMessagesForTopic(DumpJsTranslationsTopic::getName());
        foreach ($messages as $messageId => $message) {
            if ($hasDumpJsTranslationsMessage) {
                $buffer->removeMessage($messageId);
            } else {
                $hasDumpJsTranslationsMessage = true;
            }
        }
    }
}
