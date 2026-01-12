<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;

/**
 * Provides common functionality for calculating average execution times from test statistics.
 *
 * This base class manages the retrieval and calculation of average execution times for Behat tests,
 * using historical statistics to estimate test durations. Subclasses should implement
 * specific calculation strategies for different test granularities (e.g., features, scenarios).
 */
abstract class AbstractAvgTimeProvider implements StatisticRepositoryAwareInterface, AvgTimeProviderInterface
{
    /**
     * @var StatisticRepository
     */
    protected $repository;

    /**
     * @var CriteriaArrayCollection
     */
    protected $criteria;

    /**
     * @var bool
     */
    protected $isCalculated = false;

    /**
     * @var array [ID:string|int => Time:int]
     */
    protected $averageTimeTable = [];

    /**
     * @var int
     */
    protected $averageTime = 0;

    public function __construct(CriteriaArrayCollection $criteria)
    {
        $this->criteria = $criteria;
    }

    #[\Override]
    public function setRepository(StatisticRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function calculateAverageTime()
    {
        if (empty($this->averageTimeTable)) {
            return;
        }

        $this->averageTime = round(array_sum($this->averageTimeTable) / count($this->averageTimeTable));
    }

    #[\Override]
    public function getAverageTime()
    {
        if (!$this->isCalculated) {
            $this->calculate();
        }

        return $this->averageTime;
    }

    #[\Override]
    public function getAverageTimeById($id)
    {
        if (!$this->isCalculated) {
            $this->isCalculated = true;
            $this->calculate();
        }

        if (isset($this->averageTimeTable[$id])) {
            return $this->averageTimeTable[$id];
        }

        return null;
    }

    abstract protected function calculate();
}
