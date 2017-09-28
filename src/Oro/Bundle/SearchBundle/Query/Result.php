<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\Exclude;

class Result extends ArrayCollection
{
    /**
     * @Type("Oro\Bundle\SearchBundle\Query\Query")
     * @Exclude
     */
    protected $query;

    /**
     * @Type("integer")
     * @var integer
     */
    protected $recordsCount;

    /**
     * @Type("integer")
     * @var integer
     */
    protected $count;

    /**
     * Return format for count operations: ['<groupingName>' => ['<fieldValue>' => <count>], ...]
     * Return format for mathematical operations: ['<groupingName>' => <groupedValue>, ...]
     *
     * @var array
     */
    protected $groupedData;

    /**
     * @var Result\Item[]
     */
    protected $elements;

    /**
     * Initializes a new Result.
     *
     * @param Query   $query
     * @param array   $elements
     * @param integer $recordsCount
     * @param array   $groupedData
     */
    public function __construct(
        Query $query,
        array $elements = [],
        $recordsCount = 0,
        $groupedData = []
    ) {
        $this->query        = $query;
        $this->recordsCount = $recordsCount;
        $this->groupedData  = $groupedData;

        parent::__construct($elements);

        $this->count    = $this->count();
        $this->elements = $elements;
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
            'records_count' => $this->recordsCount,
            'data' => [],
            'count' => $this->count(),
            'grouped_data' => $this->groupedData
        ];

        if ($this->count()) {
            /** @var Result\Item $resultRecord */
            foreach ($this as $resultRecord) {
                $resultData['data'][] = $resultRecord->toArray();
            }
        }

        return $resultData;
    }

    /**
     * @return Result\Item[]
     */
    public function getElements()
    {
        return $this->elements;
    }
}
