<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\Repository;

use Doctrine\DBAL\Query\QueryBuilder;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;

class FeatureStatisticRepository extends StatisticRepository
{
    /**
     * @var array
     */
    protected $criteria = [];

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var array
     */
    protected $averageTimes = [];

    /**
     * @var array
     */
    protected $averageTimesCurrentBuild = [];

    /**
     * @var int
     */
    protected $averageTime;

    /**
     * @var int
     */
    protected $buildCountLimit;

    /**
     * @param string $id
     * @return float|int
     */
    public function getAverageTime($id)
    {
        if (empty($this->averageTimesCurrentBuild)) {
            $this->averageTimesCurrentBuild = $this->getFeaturesAverageTimes($this->criteria);
            $this->calculateAverageTime($this->averageTimesCurrentBuild);
        }

        if (isset($this->averageTimesCurrentBuild[$id])) {
            return $this->averageTimesCurrentBuild[$id];
        }

        if (empty($this->averageTimes)) {
            $this->averageTimes = $this->getFeaturesAverageTimes();
            if (!$this->averageTime) {
                $this->calculateAverageTime($this->averageTimes);
            }
        }

        if (isset($this->averageTimes[$id])) {
            return $this->averageTimes[$id];
        }

        return $this->averageTime;
    }

    /**
     * @param array $criteria
     * @return array ['path/to/feature' => time_in_seconds]
     */
    public function getFeaturesAverageTimes($criteria = [])
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select("path, avg(time) as time")
            ->from($this->className::getName())
            ->groupBy('path')
        ;

        if ($criteria) {
            $this->addCriteria($criteria, $queryBuilder);
        }

        if ($buildIds = $this->getBuildIds($criteria)) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('build_id', $buildIds));
        }

        $result = $queryBuilder->execute()->fetchAll();

        $paths = [];

        foreach ($result as $row) {
            $paths[$row['path']] = $row['time'];
        }

        return $this->paths = $paths;
    }

    /**
     * @param array $criteria
     * @return array
     */
    private function getBuildIds($criteria = [])
    {
        $buildIdsQueryBuilder = $this->connection->createQueryBuilder()
            ->select("build_id")
            ->from($this->className::getName())
            ->groupBy('build_id')
            ->orderBy('build_id', 'DESC')
            ->setMaxResults($this->buildCountLimit)
        ;

        if ($criteria) {
            $this->addCriteria($criteria, $buildIdsQueryBuilder);
        }
        $ids = $buildIdsQueryBuilder->execute()->fetchAll();

        $ids = array_map(function ($data) {
            return $data['build_id'];
        }, $ids);
        $ids = array_filter($ids);

        return $ids;
    }

    /**
     * @param int $buildCountLimit
     */
    public function setBuildCountLimit($buildCountLimit)
    {
        $this->buildCountLimit = $buildCountLimit;
    }

    /**
     * @param array $criteria
     */
    public function setCriteria(array $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @param array $criteria
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    private function addCriteria(array $criteria, QueryBuilder $queryBuilder)
    {
        $andExpr = $queryBuilder->expr()->andX();

        foreach ($criteria as $field => $value) {
            if (is_null($value)) {
                $andExpr->add($queryBuilder->expr()->isNull($field));
            } else {
                $valueKey = uniqid(':where_value_');
                $andExpr->add($queryBuilder->expr()->eq($field, $valueKey));
                $queryBuilder->setParameter($valueKey, $value);
            }
        }

        $queryBuilder->andWhere($andExpr);
    }

    /**
     * @param array $times
     */
    private function calculateAverageTime(array $times)
    {
        if (empty($times)) {
            return;
        }

        $average = round(array_sum($times)/count($times));
        if ($this->averageTime) {
            $average = ($average + $this->averageTime)/2;
        }

        $this->averageTime = $average;
    }
}
