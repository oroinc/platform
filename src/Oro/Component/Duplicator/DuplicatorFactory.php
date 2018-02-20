<?php

namespace Oro\Component\Duplicator;

use Oro\Component\Duplicator\Filter\FilterFactory;
use Oro\Component\Duplicator\Matcher\MatcherFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DuplicatorFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var DuplicatorInterface
     */
    protected $duplicator;

    /**
     * @var MatcherFactory
     */
    protected $matcherFactory;

    /**
     * @var FilterFactory
     */
    protected $filterFactory;

    /**
     * @return DuplicatorInterface
     */
    public function create()
    {
        if (!$this->duplicator) {
            $this->duplicator = new Duplicator();
            $this->duplicator->setFilterFactory($this->filterFactory);
            $this->duplicator->setMatcherFactory($this->matcherFactory);
        }

        return $this->duplicator;
    }

    /**
     * @param MatcherFactory $matcherFactory
     */
    public function setMatcherFactory(MatcherFactory $matcherFactory)
    {
        $this->matcherFactory = $matcherFactory;
    }

    /**
     * @param FilterFactory $filterFactory
     */
    public function setFilterFactory(FilterFactory$filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }
}
