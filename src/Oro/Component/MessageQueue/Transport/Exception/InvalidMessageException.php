<?php
namespace Oro\Component\MessageQueue\Transport\Exception;

use Oro\Component\MessageQueue\Transport\MessageInterface;

class InvalidMessageException extends Exception
{
    public static function assertMessageInstanceOf(MessageInterface $message, $class)
    {
        if (false == $message instanceof $class) {
            throw new static(sprintf(
                'A message is invalid. Message must be an instance of %s but it is %s.',
                $class,
                get_class($message)
            ));
        }
    }
}


