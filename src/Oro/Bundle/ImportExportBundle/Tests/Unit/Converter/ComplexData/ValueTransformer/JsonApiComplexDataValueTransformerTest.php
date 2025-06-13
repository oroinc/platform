<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData\ValueTransformer;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ValueTransformer\JsonApiComplexDataValueTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JsonApiComplexDataValueTransformerTest extends TestCase
{
    private ValueTransformer&MockObject $valueTransformer;
    private RequestType $requestType;
    private JsonApiComplexDataValueTransformer $jsonApiValueTransformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->valueTransformer = $this->createMock(ValueTransformer::class);
        $this->requestType = new RequestType([RequestType::REST, RequestType::JSON_API]);

        $this->jsonApiValueTransformer = new JsonApiComplexDataValueTransformer($this->valueTransformer);
    }

    public function testTransformsValueForNullValue(): void
    {
        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        self::assertNull($this->jsonApiValueTransformer->transformValue(null, DataType::INTEGER));
    }

    public function testTransformsValueWithDataType(): void
    {
        $value = '123';
        $transformedValue = 123;
        $dataType = DataType::INTEGER;

        $this->valueTransformer->expects(self::once())
            ->method('transformValue')
            ->with($value, $dataType, [ApiContext::REQUEST_TYPE => $this->requestType])
            ->willReturn($transformedValue);

        self::assertSame($transformedValue, $this->jsonApiValueTransformer->transformValue($value, $dataType));
    }

    public function testTransformsValueWithoutDataType(): void
    {
        $value = 'test';

        $this->valueTransformer->expects(self::never())
            ->method('transformValue');

        self::assertSame($value, $this->jsonApiValueTransformer->transformValue($value, null));
    }

    public function testTransformsValueWithoutDataTypeAndValueIsDateTime(): void
    {
        $value = new \DateTime('2023-01-01T00:00:00Z');
        $transformedValue = '2023-01-01T00:00:00Z';

        $this->valueTransformer->expects(self::once())
            ->method('transformValue')
            ->with($value, DataType::DATETIME, [ApiContext::REQUEST_TYPE => $this->requestType])
            ->willReturn($transformedValue);

        self::assertSame($transformedValue, $this->jsonApiValueTransformer->transformValue($value, null));
    }
}
