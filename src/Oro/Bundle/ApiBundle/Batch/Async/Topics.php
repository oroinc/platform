<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

/**
 * Provides the names of MQ topics related to the processing of asynchronous operations.
 */
class Topics
{
    public const UPDATE_LIST                   = 'oro.api.update_list';
    public const UPDATE_LIST_CREATE_CHUNK_JOBS = 'oro.api.update_list.create_chunk_jobs';
    public const UPDATE_LIST_START_CHUNK_JOBS  = 'oro.api.update_list.start_chunk_jobs';
    public const UPDATE_LIST_PROCESS_CHUNK     = 'oro.api.update_list.process_chunk';
    public const UPDATE_LIST_FINISH            = 'oro.api.update_list.finish';
}
