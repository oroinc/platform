<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use DeepCopy\Matcher\PropertyTypeMatcher;
use Oro\Component\Duplicator\Filter\ShallowCopyFilter;

/**
 * Responsible for copying behavior of DateTime type parameters.
 */
class DataTimeExtension extends AbstractDuplicatorExtension
{
    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return new ShallowCopyFilter();
    }

    /**
     * @return Matcher
     */
    public function getMatcher(): Matcher
    {
        return new PropertyTypeMatcher(\DateTime::class);
    }
}
