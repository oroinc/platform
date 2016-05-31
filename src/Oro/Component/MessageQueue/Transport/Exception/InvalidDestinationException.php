<?php
namespace Oro\Component\MessageQueue\Transport\Exception;

use Oro\Component\MessageQueue\Transport\DestinationInterface;

class InvalidDestinationException extends Exception
{
    public static function assertDestinationInstanceOf(DestinationInterface $destination, $class)
    {
        if (false == $destination instanceof $class) {
            throw new static(sprintf(
                'A destination is not understood. Destination must be an instance of %s but it is %s.',
                $class,
                get_class($destination)
            ));
        }
    }
}


