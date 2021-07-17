<?php
declare(strict_types=1);

namespace Oro\Component\Log;

use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Provides the following methods to log a message to the logger and throw an exception:
 *  - **throwErrorException()** logs an error message and throws an exception;
 *  - **throwCriticalException()** logs a critical message and throws an exception.
 *
 * @see \Oro\Component\Log\Test\LogAndThrowExceptionTestTrait simplifies unit testing the classes that use this trait.
 */
trait LogAndThrowExceptionTrait
{
    /**
     * Logs an error message and throws an exception of the specified type with the provided message text.
     * Do not use \sprintf() to add variables to the message, use placeholders instead and put variables
     * under named keys in the context as required by Oro logging conventions.
     * @see https://doc.oroinc.com/backend/logging/#message
     * @see https://github.com/Seldaek/monolog/blob/2.2.0/doc/message-structure.md
     */
    protected function throwErrorException(
        string $exceptionClassname,
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
        int $code = 0
    ): void {
        $processedMessage = (new PsrLogMessageProcessor())(['message' => $message, 'context' => $context])['message'];
        if (null !== $this->logger) {
            $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $calledIn = $trace['file'] . ':' . $trace['line'];
            $context['called_in'] = $calledIn;
            if (null !== $previous) {
                $context['exception'] = $previous;
            }
            $this->logger->error($message, $context);
        }
        throw new $exceptionClassname($processedMessage, $code, $previous);
    }

    /**
     * Logs a critical message and throws an exception of the specified type with the provided message text.
     * Do not use \sprintf() to add variables to the message, use placeholders instead and put variables
     * under named keys in the context as required by Oro logging conventions.
     * @see https://doc.oroinc.com/backend/logging/#message
     * @see https://github.com/Seldaek/monolog/blob/2.2.0/doc/message-structure.md
     */
    protected function throwCriticalException(
        string $exceptionClassname,
        string $message,
        array $context = [],
        ?\Throwable $previous = null,
        int $code = 0
    ): void {
        $processedMessage = (new PsrLogMessageProcessor())(['message' => $message, 'context' => $context])['message'];
        if (null !== $this->logger) {
            $trace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
            $calledIn = $trace['file'] . ':' . $trace['line'];
            $context['called_in'] = $calledIn;
            if (null !== $previous) {
                $context['exception'] = $previous;
            }
            $this->logger->critical($message, $context);
        }
        throw new $exceptionClassname($processedMessage, $code, $previous);
    }
}
