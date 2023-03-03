<?php
declare(strict_types=1);

namespace Oro\Component\Duplicator;

use DeepCopy\DeepCopy;
use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;

/**
 * Makes a copy of passed object in accordance with specified options.
 * To change values of a copied object used different filters.
 */
class Duplicator implements DuplicatorInterface
{
    protected ?FilterFactory  $filterFactory;
    protected ?MatcherFactory $matcherFactory;
    protected array           $defaultRules = [];

    /**
     * @param mixed $object
     * @param array $settings
     * @return mixed
     */
    public function duplicate(mixed $object, array $settings = []): mixed
    {
        $deepCopy = new DeepCopy();
        foreach ($settings as $option) {
            if (!isset($option[0]) || !isset($option[1])) {
                throw new \InvalidArgumentException('Invalid arguments to clone entity');
            }
            $filterOptions    = $option[0];
            $matcherArguments = $option[1];

            $filter = $this->getFilter($filterOptions);
            $deepCopy->addFilter($filter, $this->getMatcher($matcherArguments));
        }

        foreach ($this->defaultRules as $rule) {
            $deepCopy->addFilter($this->getFilter($rule[0]), $this->getMatcher($rule[1]));
        }

        return $deepCopy->copy($object);
    }

    /**
     * @param array $filterOptions
     * @return Filter
     * @internal param array|string $filterName
     */
    protected function getFilter(array $filterOptions): Filter
    {
        $filterName       = $filterOptions[0];
        $filterParameters = $filterOptions[1] ?? null;
        return $this->filterFactory->create(
            $filterName,
            array_filter(
                is_array($filterParameters) ? $filterParameters : [$filterParameters],
                function ($value) {
                    return $value !== null;
                }
            )
        );
    }

    /**
     * @param array $matcherArguments
     * @return Matcher
     */
    protected function getMatcher(array $matcherArguments): Matcher
    {
        $matcherKeyword = $matcherArguments[0];
        $arguments      = $matcherArguments[1];

        return $this->matcherFactory->create($matcherKeyword, $arguments);
    }

    /**
     * @param FilterFactory $filterFactory
     */
    public function setFilterFactory(FilterFactory $filterFactory): void
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * @param MatcherFactory $matcherFactory
     */
    public function setMatcherFactory(MatcherFactory $matcherFactory): void
    {
        $this->matcherFactory = $matcherFactory;
    }

    /**
     * @param array $defaultRules
     */
    public function setDefaultRules(array $defaultRules): void
    {
        $this->defaultRules = $defaultRules;
    }
}
