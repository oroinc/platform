<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

interface DependentMergeStepInterface extends MergeStepInterface
{
    /**
     * Get list of merge steps that this step depends from
     *
     * @return string
     */
    public function getDependentSteps();
}
