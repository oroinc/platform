<?php

namespace Oro\Component\MessageQueue\Job\Extension;

use Oro\Component\MessageQueue\Job\Job;

/**
 * Abstract MQ job extension
 */
abstract class AbstractExtension implements ExtensionInterface
{
    #[\Override]
    public function onPreRunUnique(Job $job)
    {
    }

    #[\Override]
    public function onPostRunUnique(Job $job, $jobResult)
    {
    }

    #[\Override]
    public function onPreCreateDelayed(Job $job)
    {
    }

    #[\Override]
    public function onPostCreateDelayed(Job $job, $createResult)
    {
    }

    #[\Override]
    public function onPreRunDelayed(Job $job)
    {
    }

    #[\Override]
    public function onPostRunDelayed(Job $job, $jobResult)
    {
    }

    #[\Override]
    public function onCancel(Job $job)
    {
    }

    #[\Override]
    public function onError(Job $job)
    {
    }
}
