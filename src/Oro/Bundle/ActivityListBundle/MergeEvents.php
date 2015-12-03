<?php

namespace Oro\Bundle\ActivityListBundle;

/**
 * Class MergeEvents
 * @package Oro\Bundle\ActivityListBundle
 */
final class MergeEvents
{
    /**
     * The BEFORE_MERGE_ACTIVITY event occurs at the very beginning of merge.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const BEFORE_MERGE_ACTIVITY = 'oro.activity_list.before_merge_activity';

    /**
     * The AFTER_MERGE_ACTIVITY event occurs at the end of merge.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const AFTER_MERGE_ACTIVITY = 'oro.activity_list.after_merge_activity';
}
