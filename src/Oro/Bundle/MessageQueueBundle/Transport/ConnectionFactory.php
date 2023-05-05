<?php

namespace Oro\Bundle\MessageQueueBundle\Transport;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\DsnBasedParameters;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Creates a message queue transport connection for the given the transport name
 */
class ConnectionFactory
{
    public static function create(
        ServiceLocator $locator,
        DsnBasedParameters $transportParameters
    ): ConnectionInterface {
        $transport = $locator->get($transportParameters->getTransportName());
        if (!$transport instanceof ConnectionInterface) {
            throw new UnexpectedTypeException($transport, ConnectionInterface::class);
        }

        return $transport;
    }
}
