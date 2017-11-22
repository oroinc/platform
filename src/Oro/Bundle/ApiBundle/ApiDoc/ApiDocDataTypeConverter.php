<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

/**
 * Helps to convert a data-type to a data-type that should be returned in API documentation.
 */
class ApiDocDataTypeConverter
{
    /** @var array [data type => data type in documentation, ...] */
    private $map;

    /**
     * @param array $map [data type => data type in documentation, ...]
     */
    public function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @param string $dataType
     * @param string $docDataType
     */
    public function addDataType($dataType, $docDataType)
    {
        $this->map[$dataType] = $docDataType;
    }

    /**
     * Converts a data-type to a data-type that should be returned in API documentation.
     *
     * @param string $dataType
     *
     * @return string
     */
    public function convertDataType($dataType)
    {
        if (!isset($this->map[$dataType])) {
            return $dataType;
        }

        return $this->map[$dataType];
    }
}
