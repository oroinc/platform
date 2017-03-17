<?php

namespace Oro\Component\Duplicator\Matcher;

interface Matcher extends \DeepCopy\Matcher\Matcher
{
    /**
     * @param object $object
     * @param string $property
     * @return boolean
     */
    public function matches($object, $property);
}
