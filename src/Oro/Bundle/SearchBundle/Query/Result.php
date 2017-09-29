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
    protected $elements;

    /**
     * @var integer
     */
    protected $recordsCount;

    /**
     * Return format for count operations: ['<groupingName>' => ['<fieldValue>' => <count>], ...]
     * Return format for mathematical operations: ['<groupingName>' => <groupedValue>, ...]
     *
     * @var array
     */
    protected $groupedData;

    /**
     * @param Query   $query
     * @param array   $elements
     * @param integer $recordsCount
     * @param array   $groupedData
     */
    public function __construct(
        Query $query,
        array $elements = [],
        $recordsCount = 0,
        array $groupedData = []
    ) {
        $this->query        = $query;
        $this->elements     = $elements;
        $this->recordsCount = $recordsCount;
        $this->groupedData  = $groupedData;

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
     * Return grouped data collected during the query execution
     *
     * @return array
     */
    public function getGroupedData()
    {
        return $this->groupedData;
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
            'grouped_data' => $this->getGroupedData()
        ];

        if ($this->count()) {
            foreach ($this->getElements() as $resultRecord) {
                $resultData['data'][] = $resultRecord->toArray();
            }
        }

        return $resultData;
    }
}
