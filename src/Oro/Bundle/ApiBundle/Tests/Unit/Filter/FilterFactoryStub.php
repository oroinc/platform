<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

class FilterFactoryStub
{
    /** @var object|null */
    private $filter;

    /**
     * @param object|null $filter
     */
    public function __construct($filter = null)
    {
        $this->filter = $filter;
    }

    /**
     * @param string $dataType
     *
     * @return object
     */
    public function create($dataType)
    {
        return $this->filter;
    }

    /**
     * @return object
     */
    public function createWithoutDataType()
    {
        return $this->filter;
    }

    /**
     * @param string $dataType
     *
     * @return object
     */
    private function privateCreate($dataType)
    {
        return $this->filter;
    }
}
