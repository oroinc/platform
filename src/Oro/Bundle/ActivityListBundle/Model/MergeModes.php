<?php

namespace Oro\Bundle\ActivityListBundle\Model;

/**
 * Defines merge mode constants for activity list merging operations.
 */
final class MergeModes
{
    /**
     * Selected value replaces value after merge
     */
    public const ACTIVITY_REPLACE = 'activity_replace';

    /**
     * Applicable for collections, will unite all values into one collection
     */
    public const ACTIVITY_UNITE = 'activity_unite';
}
