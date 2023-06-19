<?php

namespace Oro\Component\MessageQueue\Provider;

/**
 * Null implementation of JobConfigurationProviderInterface
 */
class NullJobConfigurationProvider implements JobConfigurationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTimeBeforeStaleForJobName($jobName)
    {
        return null;
    }
}
