<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

final class MergeModes
{
    /**
     * Selected value replaces value after merge
     */
    public const REPLACE = 'replace';

    /**
     * Applicable for collections, will unite all values into one collection
     */
    public const UNITE = 'unite';
}
