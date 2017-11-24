<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\StatisticRepository;

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

    /**
     * @param CriteriaArrayCollection $criteria
     */
    public function __construct(CriteriaArrayCollection $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getAverageTime()
    {
        if (!$this->isCalculated) {
            $this->calculate();
        }

        return $this->averageTime;
    }

    /**
     * {@inheritdoc}
     */
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
