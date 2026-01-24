<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

/**
 * Defines the contract for individual steps in the entity merge process.
 *
 * Implementing classes represent distinct phases of the merge operation, such as validation,
 * field merging, or entity removal. Steps are executed in a specific order determined by
 * their dependencies, allowing for modular and extensible merge workflows.
 */
interface MergeStepInterface
{
    /**
     * Run merge step
     */
    public function run(EntityData $data);
}
