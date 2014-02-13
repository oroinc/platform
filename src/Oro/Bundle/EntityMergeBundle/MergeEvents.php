<?php

namespace Oro\Bundle\EntityMergeBundle;

final class MergeEvents
{
    /**
     * The BEFORE_MERGE event occurs at the very beginning of merge.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const BEFORE_MERGE = 'oro.entity_merge.before_merge';

    /**
     * The BEFORE_MERGE event occurs at the end of merge.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const AFTER_MERGE = 'oro.entity_merge.after_merge';

    /**
     * The CREATE_METADATA event occurs at metadata creation.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent is passed.
     */
    const CREATE_METADATA = 'oro.entity_merge.create_metadata';

    /**
     * The CREATE_ENTITY_DATA event occurs at EntityData creation.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const CREATE_ENTITY_DATA = 'oro.entity_merge.create_entitydata';
}
