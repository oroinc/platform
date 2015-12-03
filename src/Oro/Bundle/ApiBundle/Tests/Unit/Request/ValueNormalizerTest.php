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
                $this->addProcessor($processorBag, 'rest.boolean', DataType::BOOLEAN, [RequestType::REST]),
                new Processor\Rest\NormalizeBoolean()
            ],
            [
                $this->addProcessor($processorBag, 'rest.datetime', DataType::DATETIME, [RequestType::REST]),
                new Processor\Rest\NormalizeDateTime()
            ],
            [
                $this->addProcessor($processorBag, 'rest.order_by', DataType::ORDER_BY, [RequestType::REST]),
                new Processor\Rest\NormalizeOrderBy()
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
            [ValueNormalizer::DEFAULT_REQUIREMENT, 'unknownType', [RequestType::REST]],
            [ValueNormalizer::DEFAULT_REQUIREMENT, DataType::STRING, [RequestType::REST]],
            [Processor\NormalizeInteger::REQUIREMENT, DataType::INTEGER, [RequestType::REST]],
            [Processor\NormalizeUnsignedInteger::REQUIREMENT, DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [Processor\Rest\NormalizeBoolean::REQUIREMENT, DataType::BOOLEAN, [RequestType::REST]],
            [Processor\Rest\NormalizeDateTime::REQUIREMENT, DataType::DATETIME, [RequestType::REST]],
            [Processor\Rest\NormalizeOrderBy::REQUIREMENT, DataType::ORDER_BY, [RequestType::REST]],
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
                [RequestType::REST]
            ],
            [
                ValueNormalizer::DEFAULT_REQUIREMENT,
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeInteger::REQUIREMENT),
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeUnsignedInteger::REQUIREMENT),
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\Rest\NormalizeBoolean::REQUIREMENT),
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\Rest\NormalizeDateTime::REQUIREMENT),
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                Processor\Rest\NormalizeOrderBy::REQUIREMENT,
                DataType::ORDER_BY,
                [RequestType::REST]
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
            ['test', 'test', 'unknownType', [RequestType::REST]],
            [null, null, DataType::STRING, [RequestType::REST]],
            [null, null, DataType::INTEGER, [RequestType::REST]],
            [null, null, DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [null, null, DataType::BOOLEAN, [RequestType::REST]],
            [null, null, DataType::DATETIME, [RequestType::REST]],
            [null, null, DataType::ORDER_BY, [RequestType::REST]],
            ['test', 'test', DataType::STRING, [RequestType::REST]],
            [123, 123, DataType::INTEGER, [RequestType::REST]],
            [[123, 456], [123, 456], DataType::INTEGER, [RequestType::REST]],
            [0, '0', DataType::INTEGER, [RequestType::REST]],
            [123, '123', DataType::INTEGER, [RequestType::REST]],
            [-123, '-123', DataType::INTEGER, [RequestType::REST]],
            [[123, -456], '123,-456', DataType::INTEGER, [RequestType::REST]],
            [123, 123, DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [[123, 456], [123, 456], DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [0, '0', DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [123, '123', DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [[123, 456], '123,456', DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [false, '0', DataType::BOOLEAN, [RequestType::REST]],
            [false, false, DataType::BOOLEAN, [RequestType::REST]],
            [false, 'false', DataType::BOOLEAN, [RequestType::REST]],
            [false, 'no', DataType::BOOLEAN, [RequestType::REST]],
            [true, true, DataType::BOOLEAN, [RequestType::REST]],
            [true, '1', DataType::BOOLEAN, [RequestType::REST]],
            [true, 'true', DataType::BOOLEAN, [RequestType::REST]],
            [true, 'yes', DataType::BOOLEAN, [RequestType::REST]],
            [[true, false], [true, false], DataType::BOOLEAN, [RequestType::REST]],
            [[true, false], '1,0', DataType::BOOLEAN, [RequestType::REST]],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                DataType::DATETIME,
                [RequestType::REST]
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
                [RequestType::REST]
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                '2010-01-28',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+00:00',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC'))
                ],
                '2010-01-28T15:00:00+00:00,2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [['fld1' => Criteria::ASC], ['fld1' => Criteria::ASC], DataType::ORDER_BY, [RequestType::REST]],
            [['fld1' => Criteria::ASC], 'fld1', DataType::ORDER_BY, [RequestType::REST]],
            [['fld1' => Criteria::DESC], '-fld1', DataType::ORDER_BY, [RequestType::REST]],
            [
                ['fld1' => Criteria::ASC, 'fld2' => Criteria::DESC],
                'fld1,-fld2',
                DataType::ORDER_BY,
                [RequestType::REST]
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
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a"',
                '1a',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected an array of integers. Given "1,2a".',
                '1,2a',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected unsigned integer value. Given "test"',
                'test',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected unsigned integer value. Given "1a"',
                '1a',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected an array of unsigned integers. Given "1,2a"',
                '1,2a',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected unsigned integer value. Given "-1"',
                '-1',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected an array of unsigned integers. Given "1,-1"',
                '1,-1',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected boolean value. Given "test"',
                'test',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected an array of booleans. Given "true,2"',
                'true,2',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected datetime value. Given "test"',
                'test',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                'Expected an array of datetimes. Given "2010-01-28T15:00:00,test"',
                '2010-01-28T15:00:00,test',
                DataType::DATETIME,
                [RequestType::REST]
            ],
        ];
    }

    /**
     * @param ProcessorBag         $processorBag
     * @param string               $processorId
     * @param string               $dataType
     * @param string|string[]|null $requestType
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
