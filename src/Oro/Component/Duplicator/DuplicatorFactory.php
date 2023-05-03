<?php
declare(strict_types=1);

namespace Oro\Component\Duplicator;

use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;

/**
 * The factory to create a service that makes a copy of an object.
 */
class DuplicatorFactory
{
    private MatcherFactory $matcherFactory;
    private FilterFactory $filterFactory;
    private ?DuplicatorInterface $duplicator = null;
    protected array $rules = [];
    public function __construct(MatcherFactory $matcherFactory, FilterFactory $filterFactory)
    {
        $this->matcherFactory = $matcherFactory;
        $this->filterFactory = $filterFactory;
    }

    public function create(): DuplicatorInterface
    {
        if (null === $this->duplicator) {
            $this->duplicator = new Duplicator();
            $this->duplicator->setFilterFactory($this->filterFactory);
            $this->duplicator->setMatcherFactory($this->matcherFactory);
            $this->duplicator->setDefaultRules($this->rules);
        }

        return $this->duplicator;
    }

    public function setMatcherFactory(MatcherFactory $matcherFactory): void
    {
        $this->matcherFactory = $matcherFactory;
    }

    public function setFilterFactory(FilterFactory $filterFactory): void
    {
        $this->filterFactory = $filterFactory;
    }

    public function addRule(array $filter, array $matcher): void
    {
        $this->rules[] = [
            $filter,
            $matcher
        ];
    }
}
