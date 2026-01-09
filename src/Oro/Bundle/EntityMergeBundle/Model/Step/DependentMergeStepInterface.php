<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

/**
 * Defines the contract for merge steps that have dependencies on other steps.
 *
 * Extends MergeStepInterface to add dependency tracking capabilities. Implementing classes
 * must declare which other merge steps they depend on, allowing the step sorter to determine
 * the correct execution order and detect circular dependencies.
 */
interface DependentMergeStepInterface extends MergeStepInterface
{
    /**
     * Get list of merge steps that this step depends from
     *
     * @return string
     */
    public function getDependentSteps();
}
