<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue as Processor;
use Oro\Bundle\ApiBundle\Processor\NormalizeValueProcessor;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Tests ValueNormalizer and normalization processors for all supported simple types
 */
class ValueNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    protected function setUp()
    {
        $processorFactory = $this->getMock('Oro\Component\ChainProcessor\ProcessorFactoryInterface');
        $processorBag     = new ProcessorBag($processorFactory);

        $this->valueNormalizer = new ValueNormalizer(
            new NormalizeValueProcessor($processorBag, 'normalize_value')
        );

        $processorMap = [
            [
                $this->addProcessor($processorBag, 'integer', DataType::INTEGER),
                new Processor\NormalizeInteger()
            ],
            [
                $this->addProcessor($processorBag, 'unsigned_integer', DataType::UNSIGNED_INTEGER),
                new Processor\NormalizeUnsignedInteger()
            ],
            [
                $this->addProcessor($processorBag, 'rest.boolean', DataType::BOOLEAN, RequestType::REST),
                new Processor\Rest\NormalizeBoolean()
            ],
            [
                $this->addProcessor($processorBag, 'rest.order_by', DataType::ORDER_BY, RequestType::REST),
                new Processor\Rest\NormalizeOrderBy()
            ],
        ];
        $processorFactory->expects($this->any())
            ->method('getProcessor')
            ->willReturnMap($processorMap);
    }

    /**
     * @dataProvider getRequirementProvider
     */
    public function testGetRequirement($expectedValue, $dataType, $requestType)
    {
        $result = $this->valueNormalizer->getRequirement($dataType, $requestType);
        $this->assertSame($expectedValue, $result);
    }

    public function getRequirementProvider()
    {
        return [
            ['.+', 'unknownType', RequestType::REST],
            ['.+', DataType::STRING, RequestType::REST],
            ['-?\d+', DataType::INTEGER, RequestType::REST],
            ['\d+', DataType::UNSIGNED_INTEGER, RequestType::REST],
            ['0|1|true|false|yes|no', DataType::BOOLEAN, RequestType::REST],
            ['-?[\w\.]+(,-?[\w\.]+)*', DataType::ORDER_BY, RequestType::REST],
        ];
    }

    /**
     * @dataProvider normalizeValueProvider
     */
    public function testNormalizeValue($expectedValue, $value, $dataType, $requestType)
    {
        $result = $this->valueNormalizer->normalizeValue($value, $dataType, $requestType);
        $this->assertSame($expectedValue, $result);
    }

    public function normalizeValueProvider()
    {
        return [
            ['test', 'test', 'unknownType', RequestType::REST],
            [null, null, DataType::STRING, RequestType::REST],
            [null, null, DataType::INTEGER, RequestType::REST],
            [null, null, DataType::UNSIGNED_INTEGER, RequestType::REST],
            [null, null, DataType::BOOLEAN, RequestType::REST],
            [null, null, DataType::ORDER_BY, RequestType::REST],
            ['test', 'test', DataType::STRING, RequestType::REST],
            [0, '0', DataType::INTEGER, RequestType::REST],
            [123, '123', DataType::INTEGER, RequestType::REST],
            [-123, '-123', DataType::INTEGER, RequestType::REST],
            [0, '0', DataType::UNSIGNED_INTEGER, RequestType::REST],
            [123, '123', DataType::UNSIGNED_INTEGER, RequestType::REST],
            [false, '0', DataType::BOOLEAN, RequestType::REST],
            [false, 'false', DataType::BOOLEAN, RequestType::REST],
            [false, 'no', DataType::BOOLEAN, RequestType::REST],
            [true, '1', DataType::BOOLEAN, RequestType::REST],
            [true, 'true', DataType::BOOLEAN, RequestType::REST],
            [true, 'yes', DataType::BOOLEAN, RequestType::REST],
            [['fld1' => Criteria::ASC], 'fld1', DataType::ORDER_BY, RequestType::REST],
            [['fld1' => Criteria::DESC], '-fld1', DataType::ORDER_BY, RequestType::REST],
            [['fld1' => Criteria::ASC, 'fld2' => Criteria::DESC], 'fld1,-fld2', DataType::ORDER_BY, RequestType::REST],
        ];
    }

    /**
     * @dataProvider normalizeInvalidValueProvider
     */
    public function testNormalizeInvalidValue($expectedExceptionMessage, $value, $dataType, $requestType)
    {
        $this->setExpectedException('\RuntimeException', $expectedExceptionMessage);
        $this->valueNormalizer->normalizeValue($value, $dataType, $requestType);
    }

    public function normalizeInvalidValueProvider()
    {
        return [
            ['Expected integer value. Given "test"', 'test', DataType::INTEGER, RequestType::REST],
            ['Expected unsigned integer value. Given "test"', 'test', DataType::UNSIGNED_INTEGER, RequestType::REST],
            ['Expected unsigned integer value. Given "-1"', '-1', DataType::UNSIGNED_INTEGER, RequestType::REST],
            ['Expected boolean value. Given "test".', 'test', DataType::BOOLEAN, RequestType::REST],
        ];
    }

    /**
     * @param ProcessorBag $processorBag
     * @param string       $processorId
     * @param string       $dataType
     * @param string|null  $requestType
     *
     * @return string
     */
    protected function addProcessor(ProcessorBag $processorBag, $processorId, $dataType, $requestType = null)
    {
        $attributes = ['dataType' => $dataType];
        if (null !== $requestType) {
            $attributes['requestType'] = $requestType;
        }
        $processorBag->addProcessor($processorId, $attributes, 'normalize_value', null, -10);

        return $processorId;
    }
}
