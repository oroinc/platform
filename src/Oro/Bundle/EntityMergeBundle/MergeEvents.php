<?php

namespace Oro\Bundle\EntityMergeBundle;

final class MergeEvents
{
    /**
     * The BEFORE_MERGE event occurs at the very beginning of merge
     */
    const BEFORE_MERGE = 'oro.entity_merge.before_merge';

    /**
     * The BEFORE_MERGE event occurs at the end of merge.
     */
    const AFTER_MERGE = 'oro.entity_merge.after_merge';
}
