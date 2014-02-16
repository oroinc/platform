<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

interface MergeStepInterface
{
    /**
     * Run merge step
     *
     * @param EntityData $data
     */
    public function run(EntityData $data);
}
