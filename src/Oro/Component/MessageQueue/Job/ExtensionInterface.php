<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * An interface for classes that can do some additional work before and after job processing.
 */
interface ExtensionInterface
{
    /**
     * Executed before unique job start process.
     *
     * @param Job $job
     */
    public function onPreRunUnique(Job $job);

    /**
     * Executed after unique job was processed.
     *
     * @param Job   $job
     * @param mixed $jobResult
     */
    public function onPostRunUnique(Job $job, $jobResult);

    /**
     * Executed after delayed job was created.
     *
     * @param Job   $job
     * @param mixed $createResult
     */
    public function onCreateDelayed(Job $job, $createResult);

    /**
     * Executed before delayed job start process.
     *
     * @param Job $job
     */
    public function onPreRunDelayed(Job $job);

    /**
     * Executed after delayed job was processed.
     *
     * @param Job   $job
     * @param mixed $jobResult
     */
    public function onPostRunDelayed(Job $job, $jobResult);
}
