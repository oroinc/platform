<?php

namespace Oro\Component\MessageQueue\Job\Extension;

use Oro\Component\MessageQueue\Job\Job;

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
     * Executed before delayed job was created.
     *
     * @param Job $job
     */
    public function onPreCreateDelayed(Job $job);

    /**
     * Executed after delayed job was created.
     *
     * @param Job   $job
     * @param mixed $createResult
     */
    public function onPostCreateDelayed(Job $job, $createResult);

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

    /**
     * Executed if root job was interrupted.
     *
     * @param Job $job
     */
    public function onCancel(Job $job);

    /**
     * Executed if job was crashed during callback processing.
     *
     * @param Job $job
     */
    public function onError(Job $job);
}
