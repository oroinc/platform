<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

/**
 * Sorts merge steps based on their dependencies using topological sorting.
 *
 * Analyzes the dependency graph of merge steps and returns them in the correct execution order.
 * Uses a topological sort algorithm to ensure that all dependencies of a step are executed before
 * the step itself. Throws an exception if circular dependencies are detected or if a declared
 * dependency cannot be resolved.
 */
class StepSorter
{
    /**
     * @param MergeStepInterface[] $steps
     * @return MergeStepInterface[]
     * @throws InvalidArgumentException
     */
    public static function getOrderedSteps(array $steps)
    {
        $result = array();
        $source = array();

        foreach ($steps as $key => $step) {
            $source[$key] = self::getDependentStepKeys($step, $steps);
        }

        while (count($result) !== count($steps)) {
            $noDependenciesKey = null;

            foreach ($source as $key => $data) {
                if (count($data) == 0 && !isset($result[$key])) {
                    $noDependenciesKey = $key;
                    break;
                }
            }

            if (null === $noDependenciesKey) {
                throw new InvalidArgumentException('Cannot resolve dependencies of merge steps.');
            }

            $result[$noDependenciesKey] = $steps[$noDependenciesKey];
            foreach ($source as &$data) {
                if (isset($data[$noDependenciesKey])) {
                    unset($data[$noDependenciesKey]);
                }
            }
        }

        return array_values($result);
    }

    /**
     * @param MergeStepInterface $step
     * @param array $steps
     * @return array
     * @throws InvalidArgumentException
     */
    protected static function getDependentStepKeys(MergeStepInterface $step, array $steps)
    {
        $stepDependencies = $step instanceof DependentMergeStepInterface ? $step->getDependentSteps() : array();

        if (!is_array($stepDependencies)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Method %s::getDependentSteps() must return an array of dependent steps class names.',
                    get_class($step)
                )
            );
        }

        $result = array();

        foreach ($stepDependencies as $stepDependency) {
            $dependencyKey = null;

            foreach ($steps as $key => $step) {
                if (get_class($step) === $stepDependency) {
                    $dependencyKey = $key;
                    break;
                }
            }

            if (null === $dependencyKey) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Cannot resolve dependency for step "%s". Step "%s" is not available.',
                        get_class($step),
                        $stepDependency
                    )
                );
            }

            $result[$dependencyKey] = true;
        }

        return $result;
    }
}
