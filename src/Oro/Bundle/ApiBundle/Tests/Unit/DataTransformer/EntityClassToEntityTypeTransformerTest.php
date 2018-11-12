<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DataTransformer;

use Oro\Bundle\ApiBundle\DataTransformer\EntityClassToEntityTypeTransformer;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class EntityClassToEntityTypeTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var EntityClassToEntityTypeTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->transformer = new EntityClassToEntityTypeTransformer(
            $this->valueNormalizer
        );
    }

    /**
     * @dataProvider emptyValueProvider
     */
    public function testTransformEmptyValue($value)
    {
        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        self::assertSame(
            $value,
            $this->transformer->transform('Test\Class', 'testField', $value, [], [])
        );
    }

    public function emptyValueProvider()
    {
        return [
            [null],
            ['']
        ];
    }

    public function testTransform()
    {
        $value = 'Test\Class1';
        $expectedValue = 'class1';
        $requestType = new RequestType([RequestType::REST]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\Class1', DataType::ENTITY_TYPE, $requestType)
            ->willReturn($expectedValue);

        self::assertEquals(
            $expectedValue,
            $this->transformer->transform(
                'Test\Class',
                'testField',
                $value,
                [],
                [Context::REQUEST_TYPE => $requestType]
            )
        );
    }
}
