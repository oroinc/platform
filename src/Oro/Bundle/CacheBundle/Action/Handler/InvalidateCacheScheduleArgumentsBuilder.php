<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

/**
 * Builds command-line arguments for scheduled cache invalidation commands.
 *
 * This class extracts cache invalidation parameters from a {@see DataStorageInterface} instance,
 * filters out internal scheduling parameters (such as invalidation time and handler service name),
 * and formats the remaining parameters as command-line arguments. The resulting arguments
 * can be passed to the {@see InvalidateCacheScheduleCommand} for execution in a scheduled context,
 * enabling asynchronous cache invalidation operations.
 */
class InvalidateCacheScheduleArgumentsBuilder implements InvalidateCacheScheduleArgumentsBuilderInterface
{
    /**
     * @param DataStorageInterface $dataStorage
     *
     * @return string[]
     */
    #[\Override]
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
