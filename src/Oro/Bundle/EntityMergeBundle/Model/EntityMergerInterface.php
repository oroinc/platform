<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

interface EntityMergerInterface
{
    public function merge(EntityData $data);
}
