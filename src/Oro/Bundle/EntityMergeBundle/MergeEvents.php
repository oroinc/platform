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

    /**
     * The CREATE_METADATA event occurs at metadata creation.
     */
    const CREATE_METADATA = 'oro.entity_merge.create_metadata';

    /**
     * The CREATE_ENTITYDATA event occurs at EntityData creation.
     */
    const CREATE_ENTITYDATA = 'oro.entity_merge.create_entitydata';
}
