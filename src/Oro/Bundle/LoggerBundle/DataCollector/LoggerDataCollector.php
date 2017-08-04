<?php

namespace Oro\Bundle\LoggerBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector as BaseDataCollector;

/**
 * Should be remove after update Symfony version 3.3+ and merge PR
 *
 * https://github.com/symfony/symfony/pull/23683
 * https://github.com/symfony/symfony/pull/23659
 *
 * TODO: https://magecore.atlassian.net/browse/BAP-15133
 */
class LoggerDataCollector extends BaseDataCollector
{
    /**
     * {@inheritdoc}
     */
    public function lateCollect()
    {
        // On event kernel.terminate ProfilerListener save profile for each sub-request and calls method lateCollect,
        // but sets of logs for each sub-request and master-requests are same,
        // so here added local caching to prevent work on the same data and fix performance in dev mode
        static $localCache;

        if ($localCache === null) {
            parent::lateCollect();
            $localCache = $this->data;
        } else {
            $this->data = $localCache;
        }
    }
}
