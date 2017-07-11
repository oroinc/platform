<?php

namespace Oro\Bundle\LoggerBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector as BaseDataCollector;

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
