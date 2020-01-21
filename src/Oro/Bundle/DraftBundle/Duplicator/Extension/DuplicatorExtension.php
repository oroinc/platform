<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;

/**
 * Base class to create extensions for Duplicator
 */
class DuplicatorExtension extends AbstractDuplicatorExtension
{
    /**
     * @var Matcher
     */
    private $matcher;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @param Matcher $matcher
     * @param Filter $filter
     */
    public function __construct(Matcher $matcher, Filter $filter)
    {
        $this->matcher = $matcher;
        $this->filter = $filter;
    }

    /**
     * @return Filter
     */
    public function getFilter(): Filter
    {
        return $this->filter;
    }

    /**
     * @return Matcher
     */
    public function getMatcher(): Matcher
    {
        return $this->matcher;
    }
}
