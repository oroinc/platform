<?php

namespace Oro\Component\MessageQueue\Provider;

/**
 * Null implementation of JobConfigurationProviderInterface
 */
class NullJobConfigurationProvider implements JobConfigurationProviderInterface
{
    #[\Override]
    public function getTimeBeforeStaleForJobName($jobName)
    {
        return null;
    }
}
