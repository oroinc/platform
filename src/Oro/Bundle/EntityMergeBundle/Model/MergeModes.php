<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

final class MergeModes
{
    /**
     * Selected value replaces value after merge
     */
    const REPLACE = 'replace';

    /**
     * Applicable for collections, will merge all values into one collection
     */
    const MERGE = 'merge';
}
