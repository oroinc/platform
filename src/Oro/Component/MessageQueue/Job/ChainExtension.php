<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * MQ job extension that contains all job extensions and process them.
 */
class ChainExtension implements ExtensionInterface
{
    /** @var ExtensionInterface[] */
    private $extensions;

    /**
     * @param ExtensionInterface[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunUnique(Job $job)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreRunUnique($job);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunUnique(Job $job, $jobResult)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostRunUnique($job, $jobResult);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onCreateDelayed(Job $job, $createResult)
    {
        foreach ($this->extensions as $extension) {
            $extension->onCreateDelayed($job, $createResult);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunDelayed(Job $job)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreRunDelayed($job);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunDelayed(Job $job, $jobResult)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostRunDelayed($job, $jobResult);
        }
    }
}
