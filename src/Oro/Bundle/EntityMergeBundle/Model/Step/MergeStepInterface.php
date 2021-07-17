<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

interface MergeStepInterface
{
    /**
     * Run merge step
     */
    public function run(EntityData $data);
}
