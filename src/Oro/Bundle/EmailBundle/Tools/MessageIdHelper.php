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
     * Extracts Message-ID from X-Oro-Message-ID header or falls back to standard Message-ID header.
     * This method should be used after sending an email to get the transport-provided Message-ID.
     */
    public static function getTransportMessageId(Message $message): string
    {
        $headers = $message->getPreparedHeaders();

        // First, check for the custom X-Oro-Message-ID header that stores transport-provided Message-ID.
        // Some transports may return identifiers that don't comply with RFC 2822.
        if ($headers->has('X-Oro-Message-ID')) {
            $customHeader = $headers->get('X-Oro-Message-ID');
            $messageId = $customHeader->getBodyAsString();

            // Ensure the Message-ID is wrapped in angle brackets
            if ($messageId && !str_starts_with($messageId, '<')) {
                $messageId = '<' . self::unwrapMessageId($messageId) . '>';
            }

            return $messageId;
        }

        // Fallback to standard Message-ID header
        return self::getMessageId($message);
    }

    /**
     * Removes angle brackets from message ID.
     */
    public static function unwrapMessageId(string $messageId): string
    {
        return trim($messageId, '<>');
    }
}
