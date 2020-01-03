<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use DeepCopy\Matcher\PropertyNameMatcher;
use Oro\Bundle\DraftBundle\Duplicator\Filter\SourceFilter;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;

/**
 * Responsible for copying behavior of draftSource parameter.
 */
class DraftSourceExtension extends AbstractDuplicatorExtension
{
    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        $source = $this->getContext()->offsetGet('source');

        return new SourceFilter($source);
    }

    /**
     * @return Matcher
     */
    public function getMatcher(): Matcher
    {
        return new PropertyNameMatcher('draftSource');
    }

    /**
     * @param DraftableInterface $source
     *
     * @return bool
     */
    public function isSupport(DraftableInterface $source): bool
    {
        return !$source->getDraftSource();
    }
}
