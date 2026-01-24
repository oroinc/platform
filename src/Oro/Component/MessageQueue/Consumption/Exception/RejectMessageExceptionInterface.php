<?php

namespace Oro\Component\MessageQueue\Consumption\Exception;

/**
 * Marks exceptions that indicate a message should be rejected from the queue.
 *
 * This interface is implemented by exceptions that signal the message queue consumer
 * to reject the current message, removing it from the queue without redelivery.
 * It serves as a contract for exceptions that represent unrecoverable message processing errors.
 */
interface RejectMessageExceptionInterface
{
}
