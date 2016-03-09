<?php

namespace Oro\Bundle\ActionBundle\Helper\Substitution;

use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;

class CircularReferenceSearch
{
    /**
     * @param array $list key->value of pairs search through
     * @param string $target key to search in list
     * @param string $point current point in list
     * @return bool
     */
    protected static function hasPointToTarget(array &$list, $target, $point)
    {
        if (null !== $point && array_key_exists($point, $list)) {
            if ($list[$point] === $target || $list[$point] === $point) {
                return true;
            } else {
                return self::hasPointToTarget($list, $target, $list[$point]);
            }
        }

        return false;
    }

    /**
     * Looks for circular reference in values -> keys in pairs, throws an exception if found.
     * @param array $pairs
     * @throws CircularReferenceException
     */
    public static function assert(array $pairs)
    {
        foreach ($pairs as $target => $replacement) {
            if (self::hasPointToTarget($pairs, $target, $replacement)) {
                throw new CircularReferenceException(
                    sprintf(
                        'Circular reference detected. On replacement %s that points tp %s target.',
                        $target,
                        $replacement
                    )
                );
            }
        }
    }
}
