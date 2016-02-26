<?php
/*
 *
 */

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Exception\ActionReferenceException;

/**
 * Encapsulation of action substitution aware methods and some helper methods.
 */
class ActionSubstitutionHelper
{
    const SUBSTITUTION_PATH_MAX_DEPTH = 10;

    /**
     * @param array $substitutions
     * @return void
     * @throws ActionReferenceException
     */
    public static function detectCircularSubstitutions(array $substitutions)
    {
        foreach ($substitutions as $target => $replacement) {
            $path = [$target];
            if (static::pointsToSame($substitutions, $target, $replacement, $path)) {
                throw ActionReferenceException::circularPath($path);
            }
        }

    }

    /**
     * @param array $list
     * @param string $target
     * @param string $point
     * @param array $path
     * @return bool
     */
    private static function pointsToSame(array &$list, $target, $point, &$path = [])
    {
        if (is_null($point)) {
            return false;
        }

        $path[] = $point;

        if (array_key_exists($point, $list)) {
            if ($list[$point] == $target || $list[$point] == $point) {
                return true;
            } else {
                return self::pointsToSame($list, $target, $list[$point], $path);
            }
        }
        return false;
    }

    /**
     * Method will replace all occurrences or substitutions in actions keeping order
     * @param array $substitutions loaded substitutions list with ['strTargetToReplaceKey'=>'strActionReplaceWithValue']
     * @param array $actions Actions matched by filters
     */
    public static function applySubstitutions(array $substitutions, array &$actions)
    {
        //storing original substitutors map
        $replacements = array_flip($substitutions);

        //clearing substitutions out of current context
        $substitutions = \array_intersect_key($substitutions, $actions);

        //if no actions to substitute in result-set
        if (!$substitutions) {
            //clearing non used but matched substitution actions from result
            $actions = array_diff_key($actions, $replacements);
            return;
        }
        $substitutions = \array_intersect($substitutions, $actionNames = array_keys($actions));

        //if no actions to substitute by (no replacements) in result set
        if (!$substitutions) {
            //clearing non used but matched replacers from result (can be removed by PO)
            $actions = array_diff_key($actions, $replacements); //if targets mentioned as replacements
            return;
        }

        //to preserve place of target (e.g. order) we will deal with sequential arrays of names and values
        $actionsList = array_values($actions);
        foreach ($substitutions as $target => $point) {

            // No need to deal with replacers as targets getFinalPoint will find all we need.
            if (array_key_exists($target, $replacements)) {
                continue;
            }

            $replacementName = ActionSubstitutionHelper::getFinalPoint($substitutions, $point);
            //getting index of target
            $pos = array_search($target, $actionNames);
            $actionsList[$pos] = $actions[$replacementName];
            $actionNames[$pos] = $replacementName;
            $replacementPos = array_search($replacementName, $actionNames);
            unset($actionNames[$replacementPos], $actionsList[$replacementPos]);
            unset($replacements[$replacementName]);
        }

        //combine new result set
        $actions = array_combine($actionNames, $actionsList);

        //clearing non used but matched substitution actions from result
        $actions = array_diff_key($actions, $replacements);

    }

    /**
     * Walks through $list array starting from $point key until find last value that is not met in $list as key
     * @param array $list
     * @param $point
     * @param array $path
     * @param int $maxDepth
     * @return string
     */
    public static function getFinalPoint(
        array &$list,
        $point,
        &$path = [],
        $maxDepth = self::SUBSTITUTION_PATH_MAX_DEPTH
    ) {
        if (array_key_exists($point, $list)) {
            $path[] = $point;
            if ($maxDepth > 0 && count($path) >= $maxDepth) {
                throw ActionReferenceException::maxDepthPath($path, $maxDepth);
            }
            return self::getFinalPoint($list, $list[$point], $path, $maxDepth);
        } else {
            return (string)$point;
        }
    }
}
