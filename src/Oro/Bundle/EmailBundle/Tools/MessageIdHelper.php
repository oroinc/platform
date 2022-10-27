<?php

namespace Oro\Bundle\EmailBundle\Tools;

use Symfony\Component\Mime\Header\IdentificationHeader;
use Symfony\Component\Mime\Message;

/**
 * Contains handy methods for working with email message id.
 * Extracts Message-ID header value from {@see Message}.
 */
class MessageIdHelper
{
    /**
     * Extracts Message-ID header value from {@see Message}.
     *
     * @param Message $message
     * @return string Message ID wrapped in angle brackets, e.g. '<sample@example.com>'
     */
    public static function getMessageId(Message $message): string
    {
        $idHeader = $message->getPreparedHeaders()->get('Message-ID');
        if (!$idHeader instanceof IdentificationHeader) {
            throw new \LogicException(
                sprintf('Message-ID header was expected to be an instance of %s', IdentificationHeader::class)
            );
        }

        return $idHeader->getBodyAsString() ?? '';
    }

    /**
     * Removes angle brackets from message ID.
     *
     * @param string $messageId
     * @return string
     */
    public static function unwrapMessageId(string $messageId): string
    {
        return trim($messageId, '<>');
    }
}
