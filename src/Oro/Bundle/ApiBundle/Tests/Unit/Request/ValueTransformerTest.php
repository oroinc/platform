<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\DataTransformer\DataTransformerRegistry;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\EntitySerializer\DataTransformerInterface;

class ValueTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerRegistry */
    private $dataTransformerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DataTransformerInterface */
    private $dataTransformer;

    /** @var ValueTransformer */
    private $valueTransformer;

    protected function setUp(): void
    {
        $this->dataTransformerRegistry = $this->createMock(DataTransformerRegistry::class);
        $this->dataTransformer = $this->createMock(DataTransformerInterface::class);

        $this->valueTransformer = new ValueTransformer(
            $this->dataTransformerRegistry,
            $this->dataTransformer
        );
    }

    public function testTransformValueWhenNotRequestTypeInContext()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The transformation context must have "requestType" attribute.');

        $value = 'value';
        $dataType = 'test_data_type';
        $context = ['context_key' => 'context_value'];

        $this->dataTransformerRegistry->expects(self::never())
            ->method('getDataTransformer');
        $this->dataTransformer->expects(self::never())
            ->method('transform');

        $this->valueTransformer->transformValue($value, $dataType, $context);
    }

    public function testTransformValueWhenDataTransformerNotFound()
    {
        $value = 'value';
        $dataType = 'test_data_type';
        $requestType = new RequestType([RequestType::REST]);
        $context = ['context_key' => 'context_value', ApiContext::REQUEST_TYPE => $requestType];

        $this->dataTransformerRegistry->expects(self::once())
            ->method('getDataTransformer')
            ->with($dataType, self::identicalTo($requestType))
            ->willReturn(null);

        $this->dataTransformer->expects(self::never())
            ->method('transform');

        self::assertSame(
            $value,
            $this->valueTransformer->transformValue($value, $dataType, $context)
        );
    }

    public function testTransformValueWhenDataTransformerFound()
    {
        $value = 'value';
        $dataType = 'test_data_type';
        $requestType = new RequestType([RequestType::REST]);
        $context = ['context_key' => 'context_value', ApiContext::REQUEST_TYPE => $requestType];
        $transformedValue = 'transformed_value';

        $dataTransformer = $this->createMock(DataTransformerInterface::class);

        $this->dataTransformerRegistry->expects(self::once())
            ->method('getDataTransformer')
            ->with($dataType, self::identicalTo($requestType))
            ->willReturn($dataTransformer);

        $this->dataTransformer->expects(self::once())
            ->method('transform')
            ->with(
                $value,
                [ConfigUtil::DATA_TYPE => $dataType, ConfigUtil::DATA_TRANSFORMER => [$dataTransformer]],
                $context
            )
            ->willReturn($transformedValue);

        self::assertSame(
            $transformedValue,
            $this->valueTransformer->transformValue($value, $dataType, $context)
        );
    }

    public function testTransformFieldValue()
    {
        $fieldValue = 'value';
        $fieldConfig = ['config_key' => 'config_value'];
        $context = ['context_key' => 'context_value'];
        $transformedValue = 'transformed_value';

        $this->dataTransformer->expects(self::once())
            ->method('transform')
            ->with($fieldValue, $fieldConfig, $context)
            ->willReturn($transformedValue);

        self::assertSame(
            $transformedValue,
            $this->valueTransformer->transformFieldValue($fieldValue, $fieldConfig, $context)
        );
    }
}
