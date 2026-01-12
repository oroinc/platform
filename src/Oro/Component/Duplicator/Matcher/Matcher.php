<?php

namespace Oro\Component\Duplicator\Matcher;

/**
 * Defines the contract for matching object properties during duplication.
 *
 * Matchers determine which properties of an object should be processed by
 * specific filters during the deep copy operation.
 */
interface Matcher extends \DeepCopy\Matcher\Matcher
{
    /**
     * @param object $object
     * @param string $property
     * @return boolean
     */
    public function matches($object, $property);
}
