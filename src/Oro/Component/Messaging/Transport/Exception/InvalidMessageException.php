<?php
namespace Oro\Component\Messaging\Transport\Exception;

use Oro\Component\Messaging\Transport\Message;

class InvalidMessageException extends Exception
{
    public static function assertMessageInstanceOf(Message $message, $class)
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


