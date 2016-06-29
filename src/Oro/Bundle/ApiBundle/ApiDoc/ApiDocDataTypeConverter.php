<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\DataTypes as ApiDocDataType;

use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Helps to convert a data-type to a data-type supported by Nelmio\ApiDocBundle
 */
class ApiDocDataTypeConverter
{
    /**
     * Converts a data-type to a data-type supported by Nelmio\ApiDocBundle.
     *
     * @param string $dataType
     *
     * @return string
     */
    public static function convertToApiDocDataType($dataType)
    {
        switch ($dataType) {
            case DataType::INTEGER:
            case DataType::UNSIGNED_INTEGER:
                return ApiDocDataType::INTEGER;
            case DataType::BOOLEAN:
                return ApiDocDataType::BOOLEAN;
            case DataType::DATETIME:
                return ApiDocDataType::DATETIME;
            case DataType::FLOAT:
            case DataType::DECIMAL:
                return ApiDocDataType::FLOAT;
        }

        return ApiDocDataType::STRING;
    }
}
