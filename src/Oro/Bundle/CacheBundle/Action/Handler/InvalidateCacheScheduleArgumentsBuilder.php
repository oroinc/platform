<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

class InvalidateCacheScheduleArgumentsBuilder implements InvalidateCacheScheduleArgumentsBuilderInterface
{
    /**
     * @param DataStorageInterface $dataStorage
     *
     * @return string[]
     */
    public function build(DataStorageInterface $dataStorage)
    {
        $excludeParameters = [
            InvalidateCacheActionScheduledHandler::PARAM_INVALIDATE_TIME,
            InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME,
        ];

        $parameters = [];
        foreach ($dataStorage->all() as $key => $value) {
            if (!in_array($key, $excludeParameters, true)) {
                $parameters[$key] = $value;
            }
        }

        return [
            sprintf(
                '%s=%s',
                InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME,
                $dataStorage->get(InvalidateCacheActionScheduledHandler::PARAM_HANDLER_SERVICE_NAME)
            ),
            sprintf('%s=%s', InvalidateCacheScheduleCommand::ARGUMENT_PARAMETERS, serialize($parameters))
        ];
    }
}
