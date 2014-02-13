<?php

namespace Oro\Bundle\EntityMergeBundle;

final class MergeEvents
{
    /**
     * The BEFORE_MERGE_ENTITY event occurs at the very beginning of merge.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const BEFORE_MERGE_ENTITY = 'oro.entity_merge.before_merge_entity';

    /**
     * The AFTER_MERGE_ENTITY event occurs at the end of merge.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const AFTER_MERGE_ENTITY = 'oro.entity_merge.after_merge_entity';

    /**
     * The BEFORE_MERGE_FIELD event occurs at the beginning of merging field.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent is passed.
     */
    const BEFORE_MERGE_FIELD = 'oro.entity_merge.before_merge_field';

    /**
     * The AFTER_MERGE_FIELD event occurs at the end of merging field.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent is passed.
     */
    const AFTER_MERGE_FIELD = 'oro.entity_merge.after_merge_field';

    /**
     * The CREATE_METADATA event occurs at metadata creation.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent is passed.
     */
    const CREATE_METADATA = 'oro.entity_merge.create_metadata';

    /**
     * The CREATE_ENTITY_DATA event occurs at EntityData creation.
     * Instance of Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent is passed.
     */
    const CREATE_ENTITY_DATA = 'oro.entity_merge.create_entity_data';
}
