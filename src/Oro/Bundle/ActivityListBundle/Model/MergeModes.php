<?php

namespace Oro\Bundle\ActivityListBundle\Model;

final class MergeModes
{
    /**
     * Selected value replaces value after merge
     */
    const ACTIVITY_REPLACE = 'activity_replace';

    /**
     * Applicable for collections, will unite all values into one collection
     */
    const ACTIVITY_UNITE = 'activity_unite';
}
