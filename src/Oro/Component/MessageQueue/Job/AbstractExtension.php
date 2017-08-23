<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Abstract MQ job extension
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function onPreRunUnique(Job $job)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunUnique(Job $job, $jobResult)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onCreateDelayed(Job $job, $createResult)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunDelayed(Job $job)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunDelayed(Job $job, $jobResult)
    {
    }
}
