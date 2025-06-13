<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData\ValueTransformer;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;

/**
 * Transforms a value to appropriate data-type that can be used in JSON:API document.
 */
class JsonApiComplexDataValueTransformer implements ComplexDataValueTransformerInterface
{
    private ?RequestType $requestType = null;

    public function __construct(
        private readonly ValueTransformer $valueTransformer
    ) {
    }

    public function transformValue(mixed $value, ?string $dataType): mixed
    {
        if (null === $value) {
            return null;
        }

        if (!$dataType && $value instanceof \DateTimeInterface) {
            $dataType = DataType::DATETIME;
        }

        if (!$dataType) {
            return $value;
        }

        if (null === $this->requestType) {
            $this->requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);
        }

        return $this->valueTransformer->transformValue(
            $value,
            $dataType,
            [ApiContext::REQUEST_TYPE => $this->requestType]
        );
    }
}
