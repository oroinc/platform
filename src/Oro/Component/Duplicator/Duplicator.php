<?php

namespace Oro\Component\Duplicator;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;

use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;

class Duplicator implements DuplicatorInterface
{
    /**
     * @var FilterFactory
     */
    protected $filterFactory;

    /**
     * @var MatcherFactory
     */
    protected $matcherFactory;

    /**
     * @param object $object
     * @param array $settings
     * @return mixed
     */
    public function duplicate($object, array $settings = [])
    {
        $deepCopy = new DeepCopy();
        foreach ($settings as $option) {
            if (!isset($option[0]) || !isset($option[1])) {
                throw new \InvalidArgumentException('Invalid arguments to clone entity');
            }
            $filterOptions = $option[0];
            $matcherArguments = $option[1];

            $filter = $this->getFilter($filterOptions);
            $deepCopy->addFilter($filter, $this->getMatcher($matcherArguments));
        }

        return $deepCopy->copy($object);
    }

    /**
     * @param $filterOptions
     * @return Filter
     * @internal param array|string $filterName
     */
    protected function getFilter($filterOptions)
    {
        $filterName = $filterOptions[0];
        $filterParameters = isset($filterOptions[1]) ? $filterOptions[1] : null;
        return $this->filterFactory->create($filterName, array_filter([$filterParameters]));
    }

    /**
     * @param $matcherArguments
     * @return Matcher
     */
    protected function getMatcher($matcherArguments)
    {
        $matcherKeyword = $matcherArguments[0];
        $arguments = $matcherArguments[1];

        return $this->matcherFactory->create($matcherKeyword, $arguments);
    }

    /**
     * @param FilterFactory $filterFactory
     */
    public function setFilterFactory($filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * @param MatcherFactory $matcherFactory
     */
    public function setMatcherFactory($matcherFactory)
    {
        $this->matcherFactory = $matcherFactory;
    }
}
