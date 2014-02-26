<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

interface EntityMergerInterface
{
    /**
     * @param EntityData $data
     */
    public function merge(EntityData $data);
}
