<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue as Processor;
use Oro\Bundle\ApiBundle\Processor\NormalizeValueProcessor;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RestRequest;
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
                $this->addProcessor($processorBag, 'rest.boolean', DataType::BOOLEAN, RequestType::REST_JSON_API),
                new Processor\RestJsonApi\NormalizeBoolean()
            ],
            [
                $this->addProcessor($processorBag, 'rest.datetime', DataType::DATETIME, RequestType::REST_JSON_API),
                new Processor\RestJsonApi\NormalizeDateTime()
            ],
            [
                $this->addProcessor($processorBag, 'rest.order_by', DataType::ORDER_BY, RequestType::REST_JSON_API),
                new Processor\RestJsonApi\NormalizeOrderBy()
            ],
        ];
        foreach ($processorMap as $val) {
            if ($val[1] instanceof StandaloneFilter) {
                $val[1]->setArrayAllowed(true);
            }
        }
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
            [ValueNormalizer::DEFAULT_REQUIREMENT, 'unknownType', RequestType::REST_JSON_API],
            [ValueNormalizer::DEFAULT_REQUIREMENT, DataType::STRING, RequestType::REST_JSON_API],
            [Processor\NormalizeInteger::REQUIREMENT, DataType::INTEGER, RequestType::REST_JSON_API],
            [Processor\NormalizeUnsignedInteger::REQUIREMENT, DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [Processor\RestJsonApi\NormalizeBoolean::REQUIREMENT, DataType::BOOLEAN, RequestType::REST_JSON_API],
            [Processor\RestJsonApi\NormalizeDateTime::REQUIREMENT, DataType::DATETIME, RequestType::REST_JSON_API],
            [Processor\RestJsonApi\NormalizeOrderBy::REQUIREMENT, DataType::ORDER_BY, RequestType::REST_JSON_API],
        ];
    }

    /**
     * @dataProvider getArrayRequirementProvider
     */
    public function testGetArrayRequirement($expectedValue, $dataType, $requestType)
    {
        $result = $this->valueNormalizer->getRequirement($dataType, $requestType, RestRequest::ARRAY_DELIMITER);
        $this->assertSame($expectedValue, $result);
    }

    public function getArrayRequirementProvider()
    {
        return [
            [
                ValueNormalizer::DEFAULT_REQUIREMENT,
                'unknownType',
                RequestType::REST_JSON_API
            ],
            [
                ValueNormalizer::DEFAULT_REQUIREMENT,
                DataType::STRING,
                RequestType::REST_JSON_API
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeInteger::REQUIREMENT),
                DataType::INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeUnsignedInteger::REQUIREMENT),
                DataType::UNSIGNED_INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                $this->getArrayRequirement(Processor\RestJsonApi\NormalizeBoolean::REQUIREMENT),
                DataType::BOOLEAN,
                RequestType::REST_JSON_API
            ],
            [
                $this->getArrayRequirement(Processor\RestJsonApi\NormalizeDateTime::REQUIREMENT),
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                Processor\RestJsonApi\NormalizeOrderBy::REQUIREMENT,
                DataType::ORDER_BY,
                RequestType::REST_JSON_API
            ],
        ];
    }

    protected function getArrayRequirement($requirement)
    {
        return sprintf('%1$s(%2$s%1$s)*', $requirement, RestRequest::ARRAY_DELIMITER);
    }

    /**
     * @dataProvider normalizeValueProvider
     */
    public function testNormalizeValue($expectedValue, $value, $dataType, $requestType)
    {
        $result = $this->valueNormalizer->normalizeValue($value, $dataType, $requestType, RestRequest::ARRAY_DELIMITER);
        if (is_object($expectedValue)) {
            $this->assertInstanceOf(get_class($expectedValue), $result);
            $this->assertEquals(get_class($expectedValue), get_class($result));
            $this->assertEquals($expectedValue, $result);
        } elseif (is_array($expectedValue)) {
            $this->assertEquals($expectedValue, $result);
        } else {
            $this->assertSame($expectedValue, $result);
        }
    }

    public function normalizeValueProvider()
    {
        return [
            ['test', 'test', 'unknownType', RequestType::REST_JSON_API],
            [null, null, DataType::STRING, RequestType::REST_JSON_API],
            [null, null, DataType::INTEGER, RequestType::REST_JSON_API],
            [null, null, DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [null, null, DataType::BOOLEAN, RequestType::REST_JSON_API],
            [null, null, DataType::DATETIME, RequestType::REST_JSON_API],
            [null, null, DataType::ORDER_BY, RequestType::REST_JSON_API],
            ['test', 'test', DataType::STRING, RequestType::REST_JSON_API],
            [123, 123, DataType::INTEGER, RequestType::REST_JSON_API],
            [[123, 456], [123, 456], DataType::INTEGER, RequestType::REST_JSON_API],
            [0, '0', DataType::INTEGER, RequestType::REST_JSON_API],
            [123, '123', DataType::INTEGER, RequestType::REST_JSON_API],
            [-123, '-123', DataType::INTEGER, RequestType::REST_JSON_API],
            [[123, -456], '123,-456', DataType::INTEGER, RequestType::REST_JSON_API],
            [123, 123, DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [[123, 456], [123, 456], DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [0, '0', DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [123, '123', DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [[123, 456], '123,456', DataType::UNSIGNED_INTEGER, RequestType::REST_JSON_API],
            [false, '0', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [false, false, DataType::BOOLEAN, RequestType::REST_JSON_API],
            [false, 'false', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [false, 'no', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [true, true, DataType::BOOLEAN, RequestType::REST_JSON_API],
            [true, '1', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [true, 'true', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [true, 'yes', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [[true, false], [true, false], DataType::BOOLEAN, RequestType::REST_JSON_API],
            [[true, false], '1,0', DataType::BOOLEAN, RequestType::REST_JSON_API],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                ],
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                ],
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                '2010-01-28',
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+00:00',
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC'))
                ],
                '2010-01-28T15:00:00+00:00,2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [['fld1' => Criteria::ASC], ['fld1' => Criteria::ASC], DataType::ORDER_BY, RequestType::REST_JSON_API],
            [['fld1' => Criteria::ASC], 'fld1', DataType::ORDER_BY, RequestType::REST_JSON_API],
            [['fld1' => Criteria::DESC], '-fld1', DataType::ORDER_BY, RequestType::REST_JSON_API],
            [
                ['fld1' => Criteria::ASC, 'fld2' => Criteria::DESC],
                'fld1,-fld2',
                DataType::ORDER_BY,
                RequestType::REST_JSON_API
            ],
        ];
    }

    /**
     * @dataProvider normalizeInvalidValueProvider
     */
    public function testNormalizeInvalidValue($expectedExceptionMessage, $value, $dataType, $requestType)
    {
        $this->setExpectedException('\UnexpectedValueException', $expectedExceptionMessage);
        $this->valueNormalizer->normalizeValue($value, $dataType, $requestType, RestRequest::ARRAY_DELIMITER);
    }

    public function normalizeInvalidValueProvider()
    {
        return [
            [
                'Expected integer value. Given "test"',
                'test',
                DataType::INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected integer value. Given "1a"',
                '1a',
                DataType::INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected an array of integers. Given "1,2a".',
                '1,2a',
                DataType::INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected unsigned integer value. Given "test"',
                'test',
                DataType::UNSIGNED_INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected unsigned integer value. Given "1a"',
                '1a',
                DataType::UNSIGNED_INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected an array of unsigned integers. Given "1,2a"',
                '1,2a',
                DataType::UNSIGNED_INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected unsigned integer value. Given "-1"',
                '-1',
                DataType::UNSIGNED_INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected an array of unsigned integers. Given "1,-1"',
                '1,-1',
                DataType::UNSIGNED_INTEGER,
                RequestType::REST_JSON_API
            ],
            [
                'Expected boolean value. Given "test"',
                'test',
                DataType::BOOLEAN,
                RequestType::REST_JSON_API
            ],
            [
                'Expected an array of booleans. Given "true,2"',
                'true,2',
                DataType::BOOLEAN,
                RequestType::REST_JSON_API
            ],
            [
                'Expected datetime value. Given "test"',
                'test',
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
            [
                'Expected an array of datetimes. Given "2010-01-28T15:00:00,test"',
                '2010-01-28T15:00:00,test',
                DataType::DATETIME,
                RequestType::REST_JSON_API
            ],
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
