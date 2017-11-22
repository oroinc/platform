<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\ArrayCollection;

class Result extends ArrayCollection
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Result\Item[]
     */
    protected $elements = null;

    /**
     * @var integer
     */
    protected $recordsCount = null;

    /**
     * Format for count function: ['<aggregatingName>' => ['<fieldValue>' => <count>], ...]
     * Format for mathematical functions: ['<aggregatingName>' => <aggregatedValue>, ...]
     *
     * @var array
     */
    protected $aggregatedData = null;

    /**
     * @param Query   $query
     * @param array   $elements
     * @param integer $recordsCount
     * @param array   $aggregatedData
     */
    public function __construct(
        Query $query,
        array $elements = [],
        $recordsCount = 0,
        array $aggregatedData = []
    ) {
        $this->query = $query;
        $this->elements = $elements;
        $this->recordsCount = $recordsCount;
        $this->aggregatedData = $aggregatedData;

        parent::__construct($elements);
    }

    /**
     * get Query object
     *
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Result\Item[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Return number of records of search query without limit parameters
     *
     * @return int
     */
    public function getRecordsCount()
    {
        return $this->recordsCount;
    }

    /**
     * Return aggregated data collected during the query execution
     * Format for count function: ['<aggregatingName>' => ['<fieldValue>' => <count>], ...]
     * Format for mathematical functions: ['<aggregatingName>' => <aggregatedValue>, ...]
     *
     * @return array
     */
    public function getAggregatedData()
    {
        return $this->aggregatedData;
    }

    /**
     * Gets the PHP array representation of this collection.
     * @return array
     */
    public function toSearchResultData()
    {
        $resultData =[
            'records_count' => $this->getRecordsCount(),
            'data' => [],
            'count' => $this->count(),
            'aggregated_data' => $this->getAggregatedData()
        ];

        if ($this->count()) {
            foreach ($this->getElements() as $resultRecord) {
                $resultData['data'][] = $resultRecord->toArray();
            }
        }

        return $resultData;
    }
}
