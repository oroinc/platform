<?php

namespace Oro\Component\MessageQueue\Job\Extension;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;

/**
 * This extension is used to fail job if out of memory error wa occurred during job processing.
 */
class OutOfMemoryJobExtension extends AbstractExtension
{
    /** @var JobProcessor */
    private $jobProcessor;

    /** @var Job */
    private static $job;

    /** @var string */
    private static $reservedMemory;

    /** @var JobProcessor */
    private static $staticJobProcessor;

    public function __construct(JobProcessor $jobProcessor)
    {
        $this->jobProcessor = $jobProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunUnique(Job $job): void
    {
        $this->init($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunUnique(Job $job, $jobResult): void
    {
        $this->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunDelayed(Job $job): void
    {
        $this->init($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunDelayed(Job $job, $jobResult): void
    {
        $this->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function onError(Job $job): void
    {
        $this->clear();
    }

    private function init(Job $job): void
    {
        if (!self::$reservedMemory) {
            self::$reservedMemory = str_repeat('x', 32 * 1024 * 1024);
            register_shutdown_function(static function () {
                if (!self::$reservedMemory) {
                    return;
                }

                self::$reservedMemory = 'x';

                $error = error_get_last();
                if (!$error) {
                    return;
                }

                if (false === ($error['type'] &= E_PARSE | E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
                    return;
                }

                if (self::$job &&
                    (
                        str_starts_with($error['message'], 'Allowed memory')
                        || str_starts_with($error['message'], 'Out of memory')
                    )
                ) {
                    self::$staticJobProcessor->failAndRedeliveryChildJob(self::$job);
                }
            });
        }

        self::$job = $job;
        self::$staticJobProcessor = $this->jobProcessor;
    }

    private function clear(): void
    {
        self::$job = null;
        self::$staticJobProcessor = null;
    }
}
