<?php
namespace Oro\Component\MessageQueue\Transport\Exception;

use Oro\Component\MessageQueue\Transport\MessageInterface;

class InvalidMessageException extends Exception
{
    /**
     * @param MessageInterface $message
     * @param string $class
     *
     * @throws static
     */
    public static function assertMessageInstanceOf(MessageInterface $message, $class)
    {
        if (!$message instanceof $class) {
            throw new static(sprintf(
                'The message must be an instance of %s but it is %s.',
                $class,
                get_class($message)
            ));
        }
    }
}
