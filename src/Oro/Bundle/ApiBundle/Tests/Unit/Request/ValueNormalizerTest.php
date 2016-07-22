<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
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

        $entityAliasResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityAliasResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityAliasResolver->expects($this->any())
            ->method('getClassByAlias')
            ->willReturnMap([['test_entity', 'Test\Entity']]);
        $entityAliasResolver->expects($this->any())
            ->method('getClassByPluralAlias')
            ->willReturnMap([['test_entities', 'Test\Entity']]);
        $entityAliasResolver->expects($this->any())
            ->method('getPluralAlias')
            ->willReturnMap([['Test\Entity', 'test_entities']]);

        $this->valueNormalizer = new ValueNormalizer(
            new NormalizeValueProcessor($processorBag, 'normalize_value')
        );

        $processorMap = [
            [
                $this->addProcessor($processorBag, 'string', DataType::STRING),
                new Processor\NormalizeString()
            ],
            [
                $this->addProcessor($processorBag, 'bigint', DataType::BIGINT),
                new Processor\NormalizeBigint()
            ],
            [
                $this->addProcessor($processorBag, 'integer', DataType::INTEGER),
                new Processor\NormalizeInteger()
            ],
            [
                $this->addProcessor($processorBag, 'unsigned_integer', DataType::UNSIGNED_INTEGER),
                new Processor\NormalizeUnsignedInteger()
            ],
            [
                $this->addProcessor($processorBag, 'boolean', DataType::BOOLEAN),
                new Processor\NormalizeBoolean()
            ],
            [
                $this->addProcessor($processorBag, 'decimal', DataType::DECIMAL),
                new Processor\NormalizeNumber()
            ],
            [
                $this->addProcessor($processorBag, 'float', DataType::FLOAT),
                new Processor\NormalizeNumber()
            ],
            [
                $this->addProcessor($processorBag, 'entityClass', DataType::ENTITY_CLASS),
                new Processor\NormalizeEntityClass($entityAliasResolver)
            ],
            [
                $this->addProcessor($processorBag, 'entityType', DataType::ENTITY_TYPE),
                new Processor\NormalizeEntityType($entityAliasResolver)
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
        $result = $this->valueNormalizer->getRequirement($dataType, new RequestType($requestType));
        $this->assertSame($expectedValue, $result);
    }

    public function getRequirementProvider()
    {
        return [
            [ValueNormalizer::DEFAULT_REQUIREMENT, 'unknownType', [RequestType::REST]],
            [ValueNormalizer::DEFAULT_REQUIREMENT, DataType::STRING, [RequestType::REST]],
            [Processor\NormalizeInteger::REQUIREMENT, DataType::INTEGER, [RequestType::REST]],
            [Processor\NormalizeBigint::REQUIREMENT, DataType::BIGINT, [RequestType::REST]],
            [Processor\NormalizeUnsignedInteger::REQUIREMENT, DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [Processor\NormalizeBoolean::REQUIREMENT, DataType::BOOLEAN, [RequestType::REST]],
            [Processor\NormalizeNumber::REQUIREMENT, DataType::DECIMAL, [RequestType::REST]],
            [Processor\NormalizeNumber::REQUIREMENT, DataType::FLOAT, [RequestType::REST]],
            [Processor\Rest\NormalizeDateTime::REQUIREMENT, DataType::DATETIME, [RequestType::REST]],
            [Processor\Rest\NormalizeOrderBy::REQUIREMENT, DataType::ORDER_BY, [RequestType::REST]],
        ];
    }

    /**
     * @dataProvider getArrayRequirementProvider
     */
    public function testGetArrayRequirement($expectedValue, $dataType, $requestType)
    {
        $result = $this->valueNormalizer->getRequirement($dataType, new RequestType($requestType), true);
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
                $this->getArrayRequirement(Processor\NormalizeBigint::REQUIREMENT),
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeUnsignedInteger::REQUIREMENT),
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeBoolean::REQUIREMENT),
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeNumber::REQUIREMENT),
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(Processor\NormalizeNumber::REQUIREMENT),
                DataType::FLOAT,
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
        return sprintf('%1$s(,%1$s)*', $requirement);
    }

    /**
     * @dataProvider normalizeValueProvider
     */
    public function testNormalizeValue($expectedValue, $value, $dataType, $requestType, $isArrayAllowed = false)
    {
        $result = $this->valueNormalizer->normalizeValue(
            $value,
            $dataType,
            new RequestType($requestType),
            $isArrayAllowed
        );
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function normalizeValueProvider()
    {
        return [
            ['test', 'test', 'unknownType', [RequestType::REST], true],
            ['test', 'test', 'unknownType', [RequestType::REST], false],
            [null, null, DataType::STRING, [RequestType::REST], true],
            [null, null, DataType::STRING, [RequestType::REST], false],
            [null, null, DataType::INTEGER, [RequestType::REST], true],
            [null, null, DataType::INTEGER, [RequestType::REST], false],
            [null, null, DataType::BIGINT, [RequestType::REST], true],
            [null, null, DataType::BIGINT, [RequestType::REST], false],
            [null, null, DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [null, null, DataType::UNSIGNED_INTEGER, [RequestType::REST], false],
            [null, null, DataType::BOOLEAN, [RequestType::REST], true],
            [null, null, DataType::BOOLEAN, [RequestType::REST], false],
            [null, null, DataType::DECIMAL, [RequestType::REST], true],
            [null, null, DataType::DECIMAL, [RequestType::REST], false],
            [null, null, DataType::FLOAT, [RequestType::REST], true],
            [null, null, DataType::FLOAT, [RequestType::REST], false],
            [null, null, DataType::DATETIME, [RequestType::REST], true],
            [null, null, DataType::DATETIME, [RequestType::REST], false],
            [null, null, DataType::ORDER_BY, [RequestType::REST], true],
            ['test', 'test', DataType::STRING, [RequestType::REST], true],
            ['test', 'test', DataType::STRING, [RequestType::REST], false],
            [['test1', 'test2'], ['test1', 'test2'], DataType::STRING, [RequestType::REST], true],
            [['test1', 'test2'], ['test1', 'test2'], DataType::STRING, [RequestType::REST], false],
            [['test1', 'test2'], 'test1,test2', DataType::STRING, [RequestType::REST], true],
            ['test1,test2', 'test1,test2', DataType::STRING, [RequestType::REST], false],
            [123, 123, DataType::INTEGER, [RequestType::REST], true],
            [123, 123, DataType::INTEGER, [RequestType::REST], false],
            [[123, 456], [123, 456], DataType::INTEGER, [RequestType::REST], true],
            [[123, 456], [123, 456], DataType::INTEGER, [RequestType::REST], false],
            [0, '0', DataType::INTEGER, [RequestType::REST], true],
            [0, '0', DataType::INTEGER, [RequestType::REST], false],
            [123, '123', DataType::INTEGER, [RequestType::REST], true],
            [123, '123', DataType::INTEGER, [RequestType::REST], false],
            [-123, '-123', DataType::INTEGER, [RequestType::REST], true],
            [-123, '-123', DataType::INTEGER, [RequestType::REST], false],
            [[123, -456], '123,-456', DataType::INTEGER, [RequestType::REST], true],
            [123456789013245, 123456789013245, DataType::BIGINT, [RequestType::REST], true],
            [123456789013245, 123456789013245, DataType::BIGINT, [RequestType::REST], false],
            [[123456789013245, 456], [123456789013245, 456], DataType::BIGINT, [RequestType::REST], true],
            [[123456789013245, 456], [123456789013245, 456], DataType::BIGINT, [RequestType::REST], false],
            ['0', '0', DataType::BIGINT, [RequestType::REST], true],
            ['0', '0', DataType::BIGINT, [RequestType::REST], false],
            ['123456789013245', '123456789013245', DataType::BIGINT, [RequestType::REST], true],
            ['123456789013245', '123456789013245', DataType::BIGINT, [RequestType::REST], false],
            ['-123456789013245', '-123456789013245', DataType::BIGINT, [RequestType::REST], true],
            ['-123456789013245', '-123456789013245', DataType::BIGINT, [RequestType::REST], false],
            [
                [123456789013245, -123456789013245],
                '123456789013245,-123456789013245',
                DataType::BIGINT,
                [RequestType::REST],
                true
            ],
            [123, 123, DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [123, 123, DataType::UNSIGNED_INTEGER, [RequestType::REST], false],
            [[123, 456], [123, 456], DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [[123, 456], [123, 456], DataType::UNSIGNED_INTEGER, [RequestType::REST], false],
            [0, '0', DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [0, '0', DataType::UNSIGNED_INTEGER, [RequestType::REST], false],
            [123, '123', DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [123, '123', DataType::UNSIGNED_INTEGER, [RequestType::REST], false],
            [[123, 456], '123,456', DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [false, '0', DataType::BOOLEAN, [RequestType::REST], true],
            [false, '0', DataType::BOOLEAN, [RequestType::REST], false],
            [false, false, DataType::BOOLEAN, [RequestType::REST], true],
            [false, false, DataType::BOOLEAN, [RequestType::REST], false],
            [false, 'false', DataType::BOOLEAN, [RequestType::REST], true],
            [false, 'false', DataType::BOOLEAN, [RequestType::REST], false],
            [false, 'no', DataType::BOOLEAN, [RequestType::REST], true],
            [false, 'no', DataType::BOOLEAN, [RequestType::REST], false],
            [true, true, DataType::BOOLEAN, [RequestType::REST], true],
            [true, true, DataType::BOOLEAN, [RequestType::REST], false],
            [true, '1', DataType::BOOLEAN, [RequestType::REST], true],
            [true, '1', DataType::BOOLEAN, [RequestType::REST], false],
            [true, 'true', DataType::BOOLEAN, [RequestType::REST], true],
            [true, 'true', DataType::BOOLEAN, [RequestType::REST], false],
            [true, 'yes', DataType::BOOLEAN, [RequestType::REST], true],
            [true, 'yes', DataType::BOOLEAN, [RequestType::REST], false],
            [[true, false], [true, false], DataType::BOOLEAN, [RequestType::REST], true],
            [[true, false], [true, false], DataType::BOOLEAN, [RequestType::REST], false],
            [[true, false], '1,0', DataType::BOOLEAN, [RequestType::REST], true],
            [123.1, 123.1, DataType::DECIMAL, [RequestType::REST], true],
            [123.1, 123.1, DataType::DECIMAL, [RequestType::REST], false],
            [[123.1, 456.1], [123.1, 456.1], DataType::DECIMAL, [RequestType::REST], true],
            [[123.1, 456.1], [123.1, 456.1], DataType::DECIMAL, [RequestType::REST], false],
            [0.0, '0', DataType::DECIMAL, [RequestType::REST], true],
            [0.0, '0', DataType::DECIMAL, [RequestType::REST], false],
            [123.0, '123', DataType::DECIMAL, [RequestType::REST], true],
            [123.0, '123', DataType::DECIMAL, [RequestType::REST], false],
            [0.01, '.01', DataType::DECIMAL, [RequestType::REST], true],
            [0.01, '.01', DataType::DECIMAL, [RequestType::REST], false],
            [-0.01, '-.01', DataType::DECIMAL, [RequestType::REST], true],
            [-0.01, '-.01', DataType::DECIMAL, [RequestType::REST], false],
            [123.1, '123.1', DataType::DECIMAL, [RequestType::REST], true],
            [123.1, '123.1', DataType::DECIMAL, [RequestType::REST], false],
            [-123.0, '-123', DataType::DECIMAL, [RequestType::REST], true],
            [-123.0, '-123', DataType::DECIMAL, [RequestType::REST], false],
            [-123.1, '-123.1', DataType::DECIMAL, [RequestType::REST], true],
            [-123.1, '-123.1', DataType::DECIMAL, [RequestType::REST], false],
            [[123.1, -456], '123.1,-456', DataType::DECIMAL, [RequestType::REST], true],
            [123.1, 123.1, DataType::FLOAT, [RequestType::REST], true],
            [123.1, 123.1, DataType::FLOAT, [RequestType::REST], false],
            [[123.1, 456.1], [123.1, 456.1], DataType::FLOAT, [RequestType::REST], true],
            [[123.1, 456.1], [123.1, 456.1], DataType::FLOAT, [RequestType::REST], false],
            [0.0, '0', DataType::FLOAT, [RequestType::REST], true],
            [0.0, '0', DataType::FLOAT, [RequestType::REST], false],
            [123.0, '123', DataType::FLOAT, [RequestType::REST], true],
            [123.0, '123', DataType::FLOAT, [RequestType::REST], false],
            [0.01, '.01', DataType::FLOAT, [RequestType::REST], true],
            [0.01, '.01', DataType::FLOAT, [RequestType::REST], false],
            [-0.01, '-.01', DataType::FLOAT, [RequestType::REST], true],
            [-0.01, '-.01', DataType::FLOAT, [RequestType::REST], false],
            [123.1, '123.1', DataType::FLOAT, [RequestType::REST], true],
            [123.1, '123.1', DataType::FLOAT, [RequestType::REST], false],
            [-123.0, '-123', DataType::FLOAT, [RequestType::REST], true],
            [-123.0, '-123', DataType::FLOAT, [RequestType::REST], false],
            [-123.1, '-123.1', DataType::FLOAT, [RequestType::REST], true],
            [-123.1, '-123.1', DataType::FLOAT, [RequestType::REST], false],
            [[123.1, -456], '123.1,-456', DataType::FLOAT, [RequestType::REST], true],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                DataType::DATETIME,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                DataType::DATETIME,
                [RequestType::REST],
                false
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
                [RequestType::REST],
                true
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
                [RequestType::REST],
                false
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                '2010-01-28',
                DataType::DATETIME,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                '2010-01-28',
                DataType::DATETIME,
                [RequestType::REST],
                false
            ],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+00:00',
                DataType::DATETIME,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+00:00',
                DataType::DATETIME,
                [RequestType::REST],
                false
            ],
            [
                new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC')),
                '2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                [RequestType::REST],
                false
            ],
            [
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00+0200', new \DateTimeZone('UTC'))
                ],
                '2010-01-28T15:00:00+00:00,2010-01-28T15:00:00+02:00',
                DataType::DATETIME,
                [RequestType::REST],
                true
            ],
            [['fld1' => Criteria::ASC], ['fld1' => Criteria::ASC], DataType::ORDER_BY, [RequestType::REST], true],
            [['fld1' => Criteria::ASC], 'fld1', DataType::ORDER_BY, [RequestType::REST], true],
            [['fld1' => Criteria::DESC], '-fld1', DataType::ORDER_BY, [RequestType::REST], true],
            [
                ['fld1' => Criteria::ASC, 'fld2' => Criteria::DESC],
                'fld1,-fld2',
                DataType::ORDER_BY,
                [RequestType::REST],
                true
            ],
            ['test_entities', 'Test\Entity', DataType::ENTITY_TYPE, [RequestType::REST], false],
            ['test_entities', 'test_entities', DataType::ENTITY_TYPE, [RequestType::REST], false],
            ['Test\Entity', 'test_entities', DataType::ENTITY_CLASS, [RequestType::REST], false],
            ['Test\Entity', 'Test\Entity', DataType::ENTITY_CLASS, [RequestType::REST], false],
        ];
    }

    /**
     * @dataProvider normalizeInvalidValueProvider
     */
    public function testNormalizeInvalidValue($expectedExceptionMessage, $value, $dataType, $requestType)
    {
        $this->setExpectedException('\UnexpectedValueException', $expectedExceptionMessage);
        $this->valueNormalizer->normalizeValue($value, $dataType, new RequestType($requestType), true);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
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
                'Expected big integer value. Given "test"',
                'test',
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                'Expected big integer value. Given "1a"',
                '1a',
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                'Expected an array of big integers. Given "1,2a".',
                '1,2a',
                DataType::BIGINT,
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
                'Expected number value. Given "test"',
                'test',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "1a"',
                '1a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given ".0a"',
                '.0a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "-.0a"',
                '-.0a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected an array of numbers. Given "1,2a".',
                '1,2a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "test"',
                'test',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "1a"',
                '1a',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given ".0a"',
                '.0a',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "-.0a"',
                '-.0a',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected an array of numbers. Given "1,2a".',
                '1,2a',
                DataType::FLOAT,
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
     * @param string|null          $dataType
     * @param string|string[]|null $requestType
     *
     * @return string
     */
    protected function addProcessor(ProcessorBag $processorBag, $processorId, $dataType = null, $requestType = null)
    {
        $attributes = [];
        if (null !== $dataType) {
            $attributes['dataType'] = $dataType;
        }
        if (null !== $requestType) {
            $attributes['requestType'] = is_array($requestType) ? ['&' => $requestType] : $requestType;
        }
        $processorBag->addProcessor($processorId, $attributes, 'normalize_value', null, -10);

        return $processorId;
    }
}
