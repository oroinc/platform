<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Matcher;

use DeepCopy\Matcher\Matcher;

/**
 * Used if need to apply filter to all properties.
 */
class GeneralMatcher implements Matcher
{
    /**
     * @inheritDoc
     */
    public function matches($object, $property): bool
    {
        return true;
    }
}
