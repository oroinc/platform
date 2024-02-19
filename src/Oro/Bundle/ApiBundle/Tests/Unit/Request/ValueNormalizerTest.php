<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue as Processor;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeValueProcessor;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolverRegistry;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

/**
 * Tests ValueNormalizer and normalization processors for all supported simple types
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ValueNormalizerTest extends \PHPUnit\Framework\TestCase
{
    private const STRING_REQUIREMENT = '.+';
    private const INTEGER_REQUIREMENT = '-?\d+';
    private const UNSIGNED_INTEGER_REQUIREMENT = '\d+';
    private const BIGINT_REQUIREMENT = '-?\d+';
    private const BOOLEAN_REQUIREMENT = '0|1|true|false|yes|no';
    private const DECIMAL_REQUIREMENT = '-?\d*\.?\d+';
    private const NUMBER_REQUIREMENT = '-?\d*\.?\d+';
    private const PERCENT100_REQUIREMENT = '-?\d*\.?\d+';
    private const DATETIME_REQUIREMENT =
        '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?';
    private const DATE_REQUIREMENT = '\d{4}(-\d{2}(-\d{2}?)?)?';
    private const TIME_REQUIREMENT = '\d{2}:\d{2}(:\d{2}(\.\d+)?)?';
    private const GUID_REQUIREMENT = '[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}';
    private const ORDER_BY_REQUIREMENT = '-?[\w\.]+(,-?[\w\.]+)*';

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $builder = new ProcessorBagConfigBuilder();
        $processorBag = new ProcessorBag($builder, $processorRegistry);

        $entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $entityAliasResolver->expects(self::any())
            ->method('getClassByAlias')
            ->willReturnMap([['test_entity', 'Test\Entity']]);
        $entityAliasResolver->expects(self::any())
            ->method('getClassByPluralAlias')
            ->willReturnMap([['test_entities', 'Test\Entity']]);
        $entityAliasResolver->expects(self::any())
            ->method('getPluralAlias')
            ->willReturnMap([['Test\Entity', 'test_entities']]);
        $entityAliasResolverRegistry = $this->createMock(EntityAliasResolverRegistry::class);
        $entityAliasResolverRegistry->expects(self::any())
            ->method('getEntityAliasResolver')
            ->willReturn($entityAliasResolver);

        $this->valueNormalizer = new ValueNormalizer(
            new NormalizeValueProcessor($processorBag, 'normalize_value')
        );

        $processorMap = [
            [
                $this->addProcessor($builder, 'string', DataType::STRING),
                new Processor\NormalizeString()
            ],
            [
                $this->addProcessor($builder, 'bigint', DataType::BIGINT),
                new Processor\NormalizeBigint()
            ],
            [
                $this->addProcessor($builder, 'smallint', DataType::SMALLINT),
                new Processor\NormalizeInteger()
            ],
            [
                $this->addProcessor($builder, 'integer', DataType::INTEGER),
                new Processor\NormalizeInteger()
            ],
            [
                $this->addProcessor($builder, 'duration', DataType::DURATION),
                new Processor\NormalizeInteger()
            ],
            [
                $this->addProcessor($builder, 'unsigned_integer', DataType::UNSIGNED_INTEGER),
                new Processor\NormalizeUnsignedInteger()
            ],
            [
                $this->addProcessor($builder, 'boolean', DataType::BOOLEAN),
                new Processor\NormalizeBoolean()
            ],
            [
                $this->addProcessor($builder, 'decimal', DataType::DECIMAL),
                new Processor\NormalizeDecimal()
            ],
            [
                $this->addProcessor($builder, 'money', DataType::MONEY),
                new Processor\NormalizeDecimal()
            ],
            [
                $this->addProcessor($builder, 'float', DataType::FLOAT),
                new Processor\NormalizeNumber()
            ],
            [
                $this->addProcessor($builder, 'percent', DataType::PERCENT),
                new Processor\NormalizeNumber()
            ],
            [
                $this->addProcessor($builder, 'percent_100', DataType::PERCENT_100),
                new Processor\NormalizePercent100()
            ],
            [
                $this->addProcessor($builder, 'guid', DataType::GUID),
                new Processor\NormalizeGuid()
            ],
            [
                $this->addProcessor($builder, 'entityClass', DataType::ENTITY_CLASS),
                new Processor\NormalizeEntityClass($entityAliasResolverRegistry)
            ],
            [
                $this->addProcessor($builder, 'entityType', DataType::ENTITY_TYPE),
                new Processor\NormalizeEntityType($entityAliasResolverRegistry)
            ],
            [
                $this->addProcessor($builder, 'rest.datetime', DataType::DATETIME, [RequestType::REST]),
                new Processor\Rest\NormalizeDateTime()
            ],
            [
                $this->addProcessor($builder, 'rest.date', DataType::DATE, [RequestType::REST]),
                new Processor\Rest\NormalizeDate()
            ],
            [
                $this->addProcessor($builder, 'rest.time', DataType::TIME, [RequestType::REST]),
                new Processor\Rest\NormalizeTime()
            ],
            [
                $this->addProcessor($builder, 'rest.order_by', DataType::ORDER_BY, [RequestType::REST]),
                new Processor\Rest\NormalizeOrderBy()
            ]
        ];
        foreach ($processorMap as $val) {
            if ($val[1] instanceof StandaloneFilter) {
                $val[1]->setArrayAllowed(true);
                $val[1]->setRangeAllowed(true);
            }
        }
        $processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnMap($processorMap);
    }

    /**
     * @dataProvider getRequirementProvider
     */
    public function testGetRequirement(string $expectedValue, string $dataType, array $requestType)
    {
        $result = $this->valueNormalizer->getRequirement($dataType, new RequestType($requestType));
        self::assertSame($expectedValue, $result);
    }

    public function getRequirementProvider(): array
    {
        return [
            [ValueNormalizer::DEFAULT_REQUIREMENT, 'unknownType', [RequestType::REST]],
            [self::STRING_REQUIREMENT, DataType::STRING, [RequestType::REST]],
            [self::INTEGER_REQUIREMENT, DataType::INTEGER, [RequestType::REST]],
            [self::INTEGER_REQUIREMENT, DataType::SMALLINT, [RequestType::REST]],
            [self::INTEGER_REQUIREMENT, DataType::DURATION, [RequestType::REST]],
            [self::BIGINT_REQUIREMENT, DataType::BIGINT, [RequestType::REST]],
            [self::UNSIGNED_INTEGER_REQUIREMENT, DataType::UNSIGNED_INTEGER, [RequestType::REST]],
            [self::BOOLEAN_REQUIREMENT, DataType::BOOLEAN, [RequestType::REST]],
            [self::DECIMAL_REQUIREMENT, DataType::DECIMAL, [RequestType::REST]],
            [self::DECIMAL_REQUIREMENT, DataType::MONEY, [RequestType::REST]],
            [self::NUMBER_REQUIREMENT, DataType::FLOAT, [RequestType::REST]],
            [self::NUMBER_REQUIREMENT, DataType::PERCENT, [RequestType::REST]],
            [self::PERCENT100_REQUIREMENT, DataType::PERCENT_100, [RequestType::REST]],
            [self::DATETIME_REQUIREMENT, DataType::DATETIME, [RequestType::REST]],
            [self::DATE_REQUIREMENT, DataType::DATE, [RequestType::REST]],
            [self::TIME_REQUIREMENT, DataType::TIME, [RequestType::REST]],
            [self::GUID_REQUIREMENT, DataType::GUID, [RequestType::REST]],
            [self::ORDER_BY_REQUIREMENT, DataType::ORDER_BY, [RequestType::REST]]
        ];
    }

    /**
     * @dataProvider getArrayRequirementProvider
     */
    public function testGetArrayRequirement(string $expectedValue, string $dataType, array $requestType): void
    {
        $result = $this->valueNormalizer->getRequirement($dataType, new RequestType($requestType), true);
        self::assertSame($expectedValue, $result);
    }

    public function getArrayRequirementProvider(): array
    {
        return [
            [
                ValueNormalizer::DEFAULT_REQUIREMENT,
                'unknownType',
                [RequestType::REST]
            ],
            [
                self::STRING_REQUIREMENT,
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::INTEGER_REQUIREMENT),
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::INTEGER_REQUIREMENT),
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::BIGINT_REQUIREMENT),
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::UNSIGNED_INTEGER_REQUIREMENT),
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::INTEGER_REQUIREMENT),
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::BOOLEAN_REQUIREMENT),
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::DECIMAL_REQUIREMENT),
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::DECIMAL_REQUIREMENT),
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::NUMBER_REQUIREMENT),
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::NUMBER_REQUIREMENT),
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::PERCENT100_REQUIREMENT),
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::DATETIME_REQUIREMENT),
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::DATE_REQUIREMENT),
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::TIME_REQUIREMENT),
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::GUID_REQUIREMENT),
                DataType::GUID,
                [RequestType::REST]
            ],
            [
                self::ORDER_BY_REQUIREMENT,
                DataType::ORDER_BY,
                [RequestType::REST]
            ]
        ];
    }

    private function getArrayRequirement(string $requirement): string
    {
        return sprintf('%1$s(,%1$s)*', $requirement);
    }

    /**
     * @dataProvider getRangeRequirementProvider
     */
    public function testGetRangeRequirement(string $expectedValue, string $dataType, array $requestType)
    {
        $result = $this->valueNormalizer->getRequirement($dataType, new RequestType($requestType), false, true);
        self::assertSame($expectedValue, $result);
    }

    public function getRangeRequirementProvider(): array
    {
        return [
            [
                ValueNormalizer::DEFAULT_REQUIREMENT,
                'unknownType',
                [RequestType::REST]
            ],
            [
                self::STRING_REQUIREMENT,
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::INTEGER_REQUIREMENT),
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::INTEGER_REQUIREMENT),
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::BIGINT_REQUIREMENT),
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::UNSIGNED_INTEGER_REQUIREMENT),
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::INTEGER_REQUIREMENT),
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::BOOLEAN_REQUIREMENT),
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::DECIMAL_REQUIREMENT),
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::DECIMAL_REQUIREMENT),
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::NUMBER_REQUIREMENT),
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::NUMBER_REQUIREMENT),
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::PERCENT100_REQUIREMENT),
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::DATETIME_REQUIREMENT),
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::DATE_REQUIREMENT),
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                $this->getRangeRequirement(self::TIME_REQUIREMENT),
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                self::GUID_REQUIREMENT,
                DataType::GUID,
                [RequestType::REST]
            ],
            [
                self::ORDER_BY_REQUIREMENT,
                DataType::ORDER_BY,
                [RequestType::REST]
            ]
        ];
    }

    private function getRangeRequirement(string $requirement): string
    {
        return sprintf('%1$s|%1$s..%1$s', $requirement);
    }

    /**
     * @dataProvider getArrayRangeRequirementProvider
     */
    public function testGetArrayRangeRequirement(string $expectedValue, string $dataType, array $requestType)
    {
        $result = $this->valueNormalizer->getRequirement($dataType, new RequestType($requestType), true, true);
        self::assertSame($expectedValue, $result);
    }

    public function getArrayRangeRequirementProvider(): array
    {
        return [
            [
                ValueNormalizer::DEFAULT_REQUIREMENT,
                'unknownType',
                [RequestType::REST]
            ],
            [
                self::STRING_REQUIREMENT,
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::INTEGER_REQUIREMENT),
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::INTEGER_REQUIREMENT),
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::BIGINT_REQUIREMENT),
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::UNSIGNED_INTEGER_REQUIREMENT),
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::INTEGER_REQUIREMENT),
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::BOOLEAN_REQUIREMENT),
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::DECIMAL_REQUIREMENT),
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::DECIMAL_REQUIREMENT),
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::NUMBER_REQUIREMENT),
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::NUMBER_REQUIREMENT),
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::PERCENT100_REQUIREMENT),
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::DATETIME_REQUIREMENT),
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::DATE_REQUIREMENT),
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                $this->getArrayRangeRequirement(self::TIME_REQUIREMENT),
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                $this->getArrayRequirement(self::GUID_REQUIREMENT),
                DataType::GUID,
                [RequestType::REST]
            ],
            [
                self::ORDER_BY_REQUIREMENT,
                DataType::ORDER_BY,
                [RequestType::REST]
            ]
        ];
    }

    private function getArrayRangeRequirement(string $requirement): string
    {
        return sprintf('%1$s|%2$s..%2$s', $this->getArrayRequirement($requirement), $requirement);
    }

    /**
     * @dataProvider normalizeValueProvider
     */
    public function testNormalizeValue(
        mixed $expectedValue,
        mixed $value,
        string $dataType,
        array $requestType,
        bool $isArrayAllowed = false
    ) {
        $result = $this->valueNormalizer->normalizeValue(
            $value,
            $dataType,
            new RequestType($requestType),
            $isArrayAllowed
        );
        self::assertNormalizedValue($expectedValue, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeValueProvider(): array
    {
        return [
            ['test', 'test', 'unknownType', [RequestType::REST], true],
            ['test', 'test', 'unknownType', [RequestType::REST], false],
            [null, null, DataType::STRING, [RequestType::REST], true],
            [null, null, DataType::STRING, [RequestType::REST], false],
            [null, null, DataType::INTEGER, [RequestType::REST], true],
            [null, null, DataType::INTEGER, [RequestType::REST], false],
            [null, null, DataType::SMALLINT, [RequestType::REST], true],
            [null, null, DataType::SMALLINT, [RequestType::REST], false],
            [null, null, DataType::BIGINT, [RequestType::REST], true],
            [null, null, DataType::BIGINT, [RequestType::REST], false],
            [null, null, DataType::UNSIGNED_INTEGER, [RequestType::REST], true],
            [null, null, DataType::UNSIGNED_INTEGER, [RequestType::REST], false],
            [null, null, DataType::DURATION, [RequestType::REST], true],
            [null, null, DataType::DURATION, [RequestType::REST], false],
            [null, null, DataType::BOOLEAN, [RequestType::REST], true],
            [null, null, DataType::BOOLEAN, [RequestType::REST], false],
            [null, null, DataType::DECIMAL, [RequestType::REST], true],
            [null, null, DataType::DECIMAL, [RequestType::REST], false],
            [null, null, DataType::MONEY, [RequestType::REST], true],
            [null, null, DataType::MONEY, [RequestType::REST], false],
            [null, null, DataType::FLOAT, [RequestType::REST], true],
            [null, null, DataType::FLOAT, [RequestType::REST], false],
            [null, null, DataType::PERCENT, [RequestType::REST], true],
            [null, null, DataType::PERCENT, [RequestType::REST], false],
            [null, null, DataType::PERCENT_100, [RequestType::REST], true],
            [null, null, DataType::PERCENT_100, [RequestType::REST], false],
            [null, null, DataType::DATETIME, [RequestType::REST], true],
            [null, null, DataType::DATETIME, [RequestType::REST], false],
            [null, null, DataType::DATE, [RequestType::REST], true],
            [null, null, DataType::DATE, [RequestType::REST], false],
            [null, null, DataType::TIME, [RequestType::REST], true],
            [null, null, DataType::TIME, [RequestType::REST], false],
            [null, null, DataType::GUID, [RequestType::REST], true],
            [null, null, DataType::GUID, [RequestType::REST], false],
            [null, null, DataType::ORDER_BY, [RequestType::REST], true],
            [' ', ' ', DataType::STRING, [RequestType::REST], true],
            [' ', ' ', DataType::STRING, [RequestType::REST], false],
            [',', ',', DataType::STRING, [RequestType::REST], false],
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
            [123, 123, DataType::SMALLINT, [RequestType::REST], true],
            [123, 123, DataType::SMALLINT, [RequestType::REST], false],
            [[123, 456], [123, 456], DataType::SMALLINT, [RequestType::REST], true],
            [[123, 456], [123, 456], DataType::SMALLINT, [RequestType::REST], false],
            [0, '0', DataType::SMALLINT, [RequestType::REST], true],
            [0, '0', DataType::SMALLINT, [RequestType::REST], false],
            [123, '123', DataType::SMALLINT, [RequestType::REST], true],
            [123, '123', DataType::SMALLINT, [RequestType::REST], false],
            [-123, '-123', DataType::SMALLINT, [RequestType::REST], true],
            [-123, '-123', DataType::SMALLINT, [RequestType::REST], false],
            [[123, -456], '123,-456', DataType::SMALLINT, [RequestType::REST], true],
            [123, 123, DataType::DURATION, [RequestType::REST], true],
            [123, 123, DataType::DURATION, [RequestType::REST], false],
            [[123, 456], [123, 456], DataType::DURATION, [RequestType::REST], true],
            [[123, 456], [123, 456], DataType::DURATION, [RequestType::REST], false],
            [0, '0', DataType::DURATION, [RequestType::REST], true],
            [0, '0', DataType::DURATION, [RequestType::REST], false],
            [123, '123', DataType::DURATION, [RequestType::REST], true],
            [123, '123', DataType::DURATION, [RequestType::REST], false],
            [-123, '-123', DataType::DURATION, [RequestType::REST], true],
            [-123, '-123', DataType::DURATION, [RequestType::REST], false],
            [[123, -456], '123,-456', DataType::DURATION, [RequestType::REST], true],
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
                ['123456789013245', '-123456789013245'],
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
            [123, 123, DataType::DECIMAL, [RequestType::REST], true],
            [123, 123, DataType::DECIMAL, [RequestType::REST], false],
            [[123, 456], [123, 456], DataType::DECIMAL, [RequestType::REST], true],
            [[123, 456], [123, 456], DataType::DECIMAL, [RequestType::REST], false],
            ['0', '0', DataType::DECIMAL, [RequestType::REST], true],
            ['0', '0', DataType::DECIMAL, [RequestType::REST], false],
            ['123', '123', DataType::DECIMAL, [RequestType::REST], true],
            ['123', '123', DataType::DECIMAL, [RequestType::REST], false],
            ['0.01', '.01', DataType::DECIMAL, [RequestType::REST], true],
            ['0.01', '.01', DataType::DECIMAL, [RequestType::REST], false],
            ['-0.01', '-.01', DataType::DECIMAL, [RequestType::REST], true],
            ['-0.01', '-.01', DataType::DECIMAL, [RequestType::REST], false],
            ['123.1', '123.1', DataType::DECIMAL, [RequestType::REST], true],
            ['123.1', '123.1', DataType::DECIMAL, [RequestType::REST], false],
            ['-123', '-123', DataType::DECIMAL, [RequestType::REST], true],
            ['-123', '-123', DataType::DECIMAL, [RequestType::REST], false],
            ['-123.1', '-123.1', DataType::DECIMAL, [RequestType::REST], true],
            ['-123.1', '-123.1', DataType::DECIMAL, [RequestType::REST], false],
            [['123.1', '-456'], '123.1,-456', DataType::DECIMAL, [RequestType::REST], true],
            [123, 123, DataType::MONEY, [RequestType::REST], true],
            [123, 123, DataType::MONEY, [RequestType::REST], false],
            [[123, 456], [123, 456], DataType::MONEY, [RequestType::REST], true],
            [[123, 456], [123, 456], DataType::MONEY, [RequestType::REST], false],
            ['0', '0', DataType::MONEY, [RequestType::REST], true],
            ['0', '0', DataType::MONEY, [RequestType::REST], false],
            ['123', '123', DataType::MONEY, [RequestType::REST], true],
            ['123', '123', DataType::MONEY, [RequestType::REST], false],
            ['0.01', '.01', DataType::MONEY, [RequestType::REST], true],
            ['0.01', '.01', DataType::MONEY, [RequestType::REST], false],
            ['-0.01', '-.01', DataType::MONEY, [RequestType::REST], true],
            ['-0.01', '-.01', DataType::MONEY, [RequestType::REST], false],
            ['123.1', '123.1', DataType::MONEY, [RequestType::REST], true],
            ['123.1', '123.1', DataType::MONEY, [RequestType::REST], false],
            ['-123', '-123', DataType::MONEY, [RequestType::REST], true],
            ['-123', '-123', DataType::MONEY, [RequestType::REST], false],
            ['-123.1', '-123.1', DataType::MONEY, [RequestType::REST], true],
            ['-123.1', '-123.1', DataType::MONEY, [RequestType::REST], false],
            [['123.1', '-456'], '123.1,-456', DataType::MONEY, [RequestType::REST], true],
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
            [[123.1, -456.0], '123.1,-456', DataType::FLOAT, [RequestType::REST], true],
            [123.1, 123.1, DataType::PERCENT, [RequestType::REST], true],
            [123.1, 123.1, DataType::PERCENT, [RequestType::REST], false],
            [[123.1, 456.1], [123.1, 456.1], DataType::PERCENT, [RequestType::REST], true],
            [[123.1, 456.1], [123.1, 456.1], DataType::PERCENT, [RequestType::REST], false],
            [0.0, '0', DataType::PERCENT, [RequestType::REST], true],
            [0.0, '0', DataType::PERCENT, [RequestType::REST], false],
            [123.0, '123', DataType::PERCENT, [RequestType::REST], true],
            [123.0, '123', DataType::PERCENT, [RequestType::REST], false],
            [0.01, '.01', DataType::PERCENT, [RequestType::REST], true],
            [0.01, '.01', DataType::PERCENT, [RequestType::REST], false],
            [-0.01, '-.01', DataType::PERCENT, [RequestType::REST], true],
            [-0.01, '-.01', DataType::PERCENT, [RequestType::REST], false],
            [123.1, '123.1', DataType::PERCENT, [RequestType::REST], true],
            [123.1, '123.1', DataType::PERCENT, [RequestType::REST], false],
            [-123.0, '-123', DataType::PERCENT, [RequestType::REST], true],
            [-123.0, '-123', DataType::PERCENT, [RequestType::REST], false],
            [-123.1, '-123.1', DataType::PERCENT, [RequestType::REST], true],
            [-123.1, '-123.1', DataType::PERCENT, [RequestType::REST], false],
            [[123.1, -456.0], '123.1,-456', DataType::PERCENT, [RequestType::REST], true],
            [123.1, 123.1, DataType::PERCENT_100, [RequestType::REST], true],
            [123.1, 123.1, DataType::PERCENT_100, [RequestType::REST], false],
            [[123.1, 456.1], [123.1, 456.1], DataType::PERCENT_100, [RequestType::REST], true],
            [[123.1, 456.1], [123.1, 456.1], DataType::PERCENT_100, [RequestType::REST], false],
            [0.0, '0', DataType::PERCENT_100, [RequestType::REST], true],
            [0.0, '0', DataType::PERCENT_100, [RequestType::REST], false],
            [1200.0, '12', DataType::PERCENT_100, [RequestType::REST], true],
            [1200.0, '12', DataType::PERCENT_100, [RequestType::REST], false],
            [1.23, '.0123', DataType::PERCENT_100, [RequestType::REST], true],
            [1.23, '.012300000000001', DataType::PERCENT_100, [RequestType::REST], true],
            [1.230000000001, '.012300000000009', DataType::PERCENT_100, [RequestType::REST], true],
            [1.23, '.0123', DataType::PERCENT_100, [RequestType::REST], false],
            [1.23, '.012300000000001', DataType::PERCENT_100, [RequestType::REST], false],
            [1.230000000001, '.012300000000009', DataType::PERCENT_100, [RequestType::REST], false],
            [-1.23, '-.0123', DataType::PERCENT_100, [RequestType::REST], true],
            [-1.23, '-.012300000000001', DataType::PERCENT_100, [RequestType::REST], true],
            [-1.230000000001, '-.012300000000009', DataType::PERCENT_100, [RequestType::REST], true],
            [-1.23, '-.0123', DataType::PERCENT_100, [RequestType::REST], false],
            [-1.23, '-.012300000000001', DataType::PERCENT_100, [RequestType::REST], false],
            [-1.230000000001, '-.012300000000009', DataType::PERCENT_100, [RequestType::REST], false],
            [123.4, '1.234', DataType::PERCENT_100, [RequestType::REST], true],
            [123.4, '1.234', DataType::PERCENT_100, [RequestType::REST], false],
            [-1200.0, '-12', DataType::PERCENT_100, [RequestType::REST], true],
            [-1200.0, '-12', DataType::PERCENT_100, [RequestType::REST], false],
            [-123.4, '-1.234', DataType::PERCENT_100, [RequestType::REST], true],
            [-123.4, '-1.234', DataType::PERCENT_100, [RequestType::REST], false],
            [[123.4, -456.0], '1.234,-4.56', DataType::PERCENT_100, [RequestType::REST], true],
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
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC'))
                ],
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC'))
                ],
                DataType::DATETIME,
                [RequestType::REST],
                true
            ],
            [
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC'))
                ],
                [
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC'))
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
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                DataType::DATE,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                DataType::DATE,
                [RequestType::REST],
                false
            ],
            [
                [
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC'))
                ],
                [
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC'))
                ],
                DataType::DATE,
                [RequestType::REST],
                true
            ],
            [
                [
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC'))
                ],
                [
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC'))
                ],
                DataType::DATE,
                [RequestType::REST],
                false
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                '2010-01-28',
                DataType::DATE,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                '2010-01-28',
                DataType::DATE,
                [RequestType::REST],
                false
            ],
            [
                [
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-29T00:00:00', new \DateTimeZone('UTC'))
                ],
                '2010-01-28,2010-01-29',
                DataType::DATE,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                DataType::TIME,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                DataType::TIME,
                [RequestType::REST],
                false
            ],
            [
                [
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC'))
                ],
                [
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC'))
                ],
                DataType::TIME,
                [RequestType::REST],
                true
            ],
            [
                [
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC'))
                ],
                [
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T15:00:00', new \DateTimeZone('UTC'))
                ],
                DataType::TIME,
                [RequestType::REST],
                false
            ],
            [
                new \DateTime('1970-01-01T10:30:59', new \DateTimeZone('UTC')),
                '10:30:59',
                DataType::TIME,
                [RequestType::REST],
                true
            ],
            [
                new \DateTime('1970-01-01T10:30:59', new \DateTimeZone('UTC')),
                '10:30:59',
                DataType::TIME,
                [RequestType::REST],
                false
            ],
            [
                [
                    new \DateTime('1970-01-01T10:30:59', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T11:45:00', new \DateTimeZone('UTC'))
                ],
                '10:30:59,11:45:00',
                DataType::TIME,
                [RequestType::REST],
                true
            ],
            [
                'EAC12975-D94D-4E96-88B1-101B99914DEF',
                'EAC12975-D94D-4E96-88B1-101B99914DEF',
                DataType::GUID,
                [RequestType::REST],
                true
            ],
            [
                'EAC12975-D94D-4E96-88B1-101B99914DEF',
                'EAC12975-D94D-4E96-88B1-101B99914DEF',
                DataType::GUID,
                [RequestType::REST],
                false
            ],
            [
                ['EAC12975-D94D-4E96-88B1-101B99914DEF', '7eab7435-44bb-493a-9bda-dea3fda3c0d9'],
                ['EAC12975-D94D-4E96-88B1-101B99914DEF', '7eab7435-44bb-493a-9bda-dea3fda3c0d9'],
                DataType::GUID,
                [RequestType::REST],
                true
            ],
            [
                ['EAC12975-D94D-4E96-88B1-101B99914DEF', '7eab7435-44bb-493a-9bda-dea3fda3c0d9'],
                ['EAC12975-D94D-4E96-88B1-101B99914DEF', '7eab7435-44bb-493a-9bda-dea3fda3c0d9'],
                DataType::GUID,
                [RequestType::REST],
                false
            ],
            [
                ['EAC12975-D94D-4E96-88B1-101B99914DEF', '7eab7435-44bb-493a-9bda-dea3fda3c0d9'],
                'EAC12975-D94D-4E96-88B1-101B99914DEF,7eab7435-44bb-493a-9bda-dea3fda3c0d9',
                DataType::GUID,
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
            ['Test\Entity', 'Test\Entity', DataType::ENTITY_CLASS, [RequestType::REST], false]
        ];
    }

    /**
     * @dataProvider normalizeRangeValueProvider
     */
    public function testNormalizeRangeValue(Range $expectedValue, Range|string $value, string $dataType)
    {
        $result = $this->valueNormalizer->normalizeValue(
            $value,
            $dataType,
            new RequestType([RequestType::REST]),
            true,
            true
        );
        self::assertNormalizedValue($expectedValue, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeRangeValueProvider(): array
    {
        return [
            [new Range('test1', 'test2'), new Range('test1', 'test2'), DataType::STRING],
            [new Range('test1', 'test2'), 'test1..test2', DataType::STRING],
            [new Range(' ', ' '), ' .. ', DataType::STRING],
            [new Range(' ', 'test2'), ' ..test2', DataType::STRING],
            [new Range('test1', ' '), 'test1.. ', DataType::STRING],
            [new Range(123, 456), new Range(123, 456), DataType::INTEGER],
            [new Range(123, 456), '123..456', DataType::INTEGER],
            [new Range(-456, -123), '-456..-123', DataType::INTEGER],
            [new Range(123, 456), new Range(123, 456), DataType::SMALLINT],
            [new Range(123, 456), '123..456', DataType::SMALLINT],
            [new Range(-456, -123), '-456..-123', DataType::SMALLINT],
            [new Range(123, 456), new Range(123, 456), DataType::DURATION],
            [new Range(123, 456), '123..456', DataType::DURATION],
            [new Range(-456, -123), '-456..-123', DataType::DURATION],
            [new Range(123, 456), new Range(123, 456), DataType::BIGINT],
            [
                new Range('123456789013245', '234567890132456'),
                '123456789013245..234567890132456',
                DataType::BIGINT
            ],
            [
                new Range('-234567890132456', '-123456789013245'),
                '-234567890132456..-123456789013245',
                DataType::BIGINT
            ],
            [new Range(123, 456), new Range(123, 456), DataType::UNSIGNED_INTEGER],
            [new Range(123, 456), '123..456', DataType::UNSIGNED_INTEGER],
            [new Range(false, true), new Range(false, true), DataType::BOOLEAN],
            [new Range(false, true), '0..1', DataType::BOOLEAN],
            [new Range(false, true), 'false..true', DataType::BOOLEAN],
            [new Range(false, true), 'no..yes', DataType::BOOLEAN],
            [new Range(123, 456), new Range(123, 456), DataType::DECIMAL],
            [new Range('0.123', '0.456'), '0.123..0.456', DataType::DECIMAL],
            [new Range('0.123', '0.456'), '.123...456', DataType::DECIMAL],
            [new Range('-0.456', '-0.123'), '-0.456..-0.123', DataType::DECIMAL],
            [new Range('-0.456', '-0.123'), '-.456..-.123', DataType::DECIMAL],
            [new Range(123, 456), new Range(123, 456), DataType::MONEY],
            [new Range('0.123', '0.456'), '0.123..0.456', DataType::MONEY],
            [new Range('0.123', '0.456'), '.123...456', DataType::MONEY],
            [new Range('-0.456', '-0.123'), '-0.456..-0.123', DataType::MONEY],
            [new Range('-0.456', '-0.123'), '-.456..-.123', DataType::MONEY],
            [new Range(123, 456), new Range(123, 456), DataType::FLOAT],
            [new Range(0.123, 0.456), '0.123..0.456', DataType::FLOAT],
            [new Range(0.123, 0.456), '.123...456', DataType::FLOAT],
            [new Range(-0.456, -0.123), '-0.456..-0.123', DataType::FLOAT],
            [new Range(-0.456, -0.123), '-.456..-.123', DataType::FLOAT],
            [new Range(123, 456), new Range(123, 456), DataType::PERCENT],
            [new Range(0.123, 0.456), '0.123..0.456', DataType::PERCENT],
            [new Range(0.123, 0.456), '.123...456', DataType::PERCENT],
            [new Range(-0.456, -0.123), '-0.456..-0.123', DataType::PERCENT],
            [new Range(-0.456, -0.123), '-.456..-.123', DataType::PERCENT],
            [new Range(12, 45), new Range(12, 45), DataType::PERCENT_100],
            [new Range(123.4, 456.0), '1.234..4.56', DataType::PERCENT_100],
            [new Range(12.3, 45.6), '.123...456', DataType::PERCENT_100],
            [new Range(-45.6, -12.3), '-0.456..-0.123', DataType::PERCENT_100],
            [new Range(-45.6, -12.3), '-.456..-.123', DataType::PERCENT_100],
            [
                new Range(
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:01', new \DateTimeZone('UTC'))
                ),
                new Range(
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:01', new \DateTimeZone('UTC'))
                ),
                DataType::DATETIME
            ],
            [
                new Range(
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:01', new \DateTimeZone('UTC'))
                ),
                '2010-01-28T15:00:00..2010-01-28T15:00:01',
                DataType::DATETIME
            ],
            [
                new Range(
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-29T00:00:00', new \DateTimeZone('UTC'))
                ),
                '2010-01-28..2010-01-29',
                DataType::DATETIME
            ],
            [
                new Range(
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:01', new \DateTimeZone('UTC'))
                ),
                new Range(
                    new \DateTime('2010-01-28T15:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-28T15:00:01', new \DateTimeZone('UTC'))
                ),
                DataType::DATE
            ],
            [
                new Range(
                    new \DateTime('2010-01-28T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('2010-01-29T00:00:00', new \DateTimeZone('UTC'))
                ),
                '2010-01-28..2010-01-29',
                DataType::DATE
            ],
            [
                new Range(
                    new \DateTime('1970-01-01T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T00:00:01', new \DateTimeZone('UTC'))
                ),
                new Range(
                    new \DateTime('1970-01-01T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T00:00:01', new \DateTimeZone('UTC'))
                ),
                DataType::TIME
            ],
            [
                new Range(
                    new \DateTime('1970-01-01T00:00:00', new \DateTimeZone('UTC')),
                    new \DateTime('1970-01-01T00:00:01', new \DateTimeZone('UTC'))
                ),
                '00:00:00..00:00:01',
                DataType::TIME
            ]
        ];
    }

    /**
     * @dataProvider normalizeInvalidValueProvider
     */
    public function testNormalizeInvalidValue(
        string $expectedExceptionMessage,
        string $value,
        string $dataType,
        array $requestType
    ) {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->valueNormalizer->normalizeValue($value, $dataType, new RequestType($requestType), true);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeInvalidValueProvider(): array
    {
        return [
            [
                'Expected string value. Given "".',
                '',
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "".',
                '',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected an array of strings. Given ",".',
                ',',
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "test".',
                'test',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a".',
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
                'Expected integer value. Given "test".',
                'test',
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a".',
                '1a',
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                'Expected an array of integers. Given "1,2a".',
                '1,2a',
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                'Expected big integer value. Given "test".',
                'test',
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                'Expected big integer value. Given "1a".',
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
                'Expected unsigned integer value. Given "test".',
                'test',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected unsigned integer value. Given "1a".',
                '1a',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected an array of unsigned integers. Given "1,2a".',
                '1,2a',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected unsigned integer value. Given "-1".',
                '-1',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected an array of unsigned integers. Given "1,-1".',
                '1,-1',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "test".',
                'test',
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a".',
                '1a',
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                'Expected an array of integers. Given "1,2a".',
                '1,2a',
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                'Expected boolean value. Given "test".',
                'test',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected an array of booleans. Given "true,2".',
                'true,2',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "test".',
                'test',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "1a".',
                '1a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given ".0a".',
                '.0a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "-.0a".',
                '-.0a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected an array of decimals. Given "1,2a".',
                '1,2a',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "test".',
                'test',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "1a".',
                '1a',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given ".0a".',
                '.0a',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "-.0a".',
                '-.0a',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected an array of decimals. Given "1,2a".',
                '1,2a',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "test".',
                'test',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "1a".',
                '1a',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given ".0a".',
                '.0a',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "-.0a".',
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
                'Expected number value. Given "test".',
                'test',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "1a".',
                '1a',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given ".0a".',
                '.0a',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "-.0a".',
                '-.0a',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected an array of numbers. Given "1,2a".',
                '1,2a',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "test".',
                'test',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "1a".',
                '1a',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given ".0a".',
                '.0a',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "-.0a".',
                '-.0a',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected an array of numbers. Given "1,2a".',
                '1,2a',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected datetime value. Given "test".',
                'test',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                'Expected an array of datetimes. Given "2010-01-28T15:00:00,test".',
                '2010-01-28T15:00:00,test',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                'Expected date value. Given "test".',
                'test',
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                'Expected date value. Given "2010-01-28T15:00:00".',
                '2010-01-28T15:00:00',
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                'Expected an array of dates. Given "2010-01-28,test".',
                '2010-01-28,test',
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                'Expected time value. Given "test".',
                'test',
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                'Expected time value. Given "2010-01-28T10:30:59".',
                '2010-01-28T10:30:59',
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                'Expected an array of times. Given "10:30:59,test".',
                '10:30:59,test',
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                'Expected GUID value. Given "test".',
                'test',
                DataType::GUID,
                [RequestType::REST]
            ],
            [
                'Expected GUID value. Given "7eab7435-44bb-493a-9bda-dea3fda3c0dh".',
                '7eab7435-44bb-493a-9bda-dea3fda3c0dh',
                DataType::GUID,
                [RequestType::REST]
            ],
            [
                'Expected GUID value. Given "7eab7435-44bb-493a-9bda-dea3fda3c0d91".',
                '7eab7435-44bb-493a-9bda-dea3fda3c0d91',
                DataType::GUID,
                [RequestType::REST]
            ],
            [
                'Expected an array of GUIDs. Given '
                . '"EAC12975-D94D-4E96-88B1-101B99914DEF,7eab7435-44bb-493a-9bda-dea3fda3c0dh".',
                'EAC12975-D94D-4E96-88B1-101B99914DEF,7eab7435-44bb-493a-9bda-dea3fda3c0dh',
                DataType::GUID,
                [RequestType::REST]
            ]
        ];
    }

    /**
     * @dataProvider normalizeInvalidRangeValueProvider
     */
    public function testNormalizeInvalidRangeValue(
        string $expectedExceptionMessage,
        string $value,
        string $dataType,
        array $requestType
    ) {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->valueNormalizer->normalizeValue($value, $dataType, new RequestType($requestType), true, true);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeInvalidRangeValueProvider(): array
    {
        return [
            [
                'Expected a pair of strings (string..string). Given "..".',
                '..',
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "..".',
                '..',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of strings (string..string). Given "..test".',
                '..test',
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "..1".',
                '..1',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of strings (string..string). Given "test..".',
                'test..',
                DataType::STRING,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1..".',
                '1..',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a".',
                '1a',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1a..2".',
                '1a..2',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1..2a".',
                '1..2a',
                DataType::INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a".',
                '1a',
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1a..2".',
                '1a..2',
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1..2a".',
                '1..2a',
                DataType::SMALLINT,
                [RequestType::REST]
            ],
            [
                'Expected big integer value. Given "1a".',
                '1a',
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of big integers (big integer..big integer). Given "1a..2".',
                '1a..2',
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of big integers (big integer..big integer). Given "1..2a".',
                '1..2a',
                DataType::BIGINT,
                [RequestType::REST]
            ],
            [
                'Expected unsigned integer value. Given "1a".',
                '1a',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of unsigned integers (unsigned integer..unsigned integer). Given "1a..2".',
                '1a..2',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of unsigned integers (unsigned integer..unsigned integer). Given "1..2a".',
                '1..2a',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of unsigned integers (unsigned integer..unsigned integer). Given "-1..2".',
                '-1..2',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected a pair of unsigned integers (unsigned integer..unsigned integer). Given "1..-2".',
                '1..-2',
                DataType::UNSIGNED_INTEGER,
                [RequestType::REST]
            ],
            [
                'Expected integer value. Given "1a".',
                '1a',
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1a..2".',
                '1a..2',
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                'Expected a pair of integers (integer..integer). Given "1..2a".',
                '1..2a',
                DataType::DURATION,
                [RequestType::REST]
            ],
            [
                'Expected boolean value. Given "test".',
                'test',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected a pair of booleans (boolean..boolean). Given "false..test".',
                'false..test',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected a pair of booleans (boolean..boolean). Given "test..true".',
                'test..true',
                DataType::BOOLEAN,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "test".',
                'test',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected a pair of decimals (decimal..decimal). Given "test..1.1".',
                'test..1.1',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected a pair of decimals (decimal..decimal). Given "1.1..test".',
                '1.1..test',
                DataType::DECIMAL,
                [RequestType::REST]
            ],
            [
                'Expected decimal value. Given "test".',
                'test',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected a pair of decimals (decimal..decimal). Given "test..1.1".',
                'test..1.1',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected a pair of decimals (decimal..decimal). Given "1.1..test".',
                '1.1..test',
                DataType::MONEY,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "test".',
                'test',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of numbers (number..number). Given "test..1.1".',
                'test..1.1',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of numbers (number..number). Given "1.1..test".',
                '1.1..test',
                DataType::FLOAT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "test".',
                'test',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of numbers (number..number). Given "test..0.1".',
                'test..0.1',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected a pair of numbers (number..number). Given "0.1..test".',
                '0.1..test',
                DataType::PERCENT,
                [RequestType::REST]
            ],
            [
                'Expected number value. Given "test".',
                'test',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected a pair of numbers (number..number). Given "test..0.1".',
                'test..0.1',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected a pair of numbers (number..number). Given "0.1..test".',
                '0.1..test',
                DataType::PERCENT_100,
                [RequestType::REST]
            ],
            [
                'Expected datetime value. Given "test".',
                'test',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                'Expected a pair of datetimes (datetime..datetime). Given "test..2010-01-28T15:00:00".',
                'test..2010-01-28T15:00:00',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                'Expected a pair of datetimes (datetime..datetime). Given "2010-01-28T15:00:00..test".',
                '2010-01-28T15:00:00..test',
                DataType::DATETIME,
                [RequestType::REST]
            ],
            [
                'Expected date value. Given "test".',
                'test',
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                'Expected a pair of dates (date..date). Given "test..2010-01-28".',
                'test..2010-01-28',
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                'Expected a pair of dates (date..date). Given "2010-01-28..test".',
                '2010-01-28..test',
                DataType::DATE,
                [RequestType::REST]
            ],
            [
                'Expected time value. Given "test".',
                'test',
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                'Expected a pair of times (time..time). Given "test..15:00:00".',
                'test..15:00:00',
                DataType::TIME,
                [RequestType::REST]
            ],
            [
                'Expected a pair of times (time..time). Given "15:00:00..test".',
                '15:00:00..test',
                DataType::TIME,
                [RequestType::REST]
            ]
        ];
    }

    private function addProcessor(
        ProcessorBagConfigBuilder $processorBagConfigBuilder,
        string $processorId,
        string $dataType,
        string|array|null $requestType = null
    ): string {
        $attributes = [];
        if (null !== $requestType) {
            $attributes['requestType'] = is_array($requestType) ? ['&' => $requestType] : $requestType;
        }
        $processorBagConfigBuilder->addProcessor(
            $processorId,
            $attributes,
            'normalize_value.' . $dataType,
            null,
            -10
        );

        return $processorId;
    }

    private static function assertNormalizedValue(mixed $expected, mixed $actual, string $message = ''): void
    {
        if (is_object($expected)) {
            self::assertInstanceOf(get_class($expected), $actual, $message);
            self::assertEquals(get_class($expected), get_class($actual), $message);
            if ($expected instanceof Range) {
                self::assertNormalizedValue($expected->getFromValue(), $actual->getFromValue(), 'Range.fromValue');
                self::assertNormalizedValue($expected->getToValue(), $actual->getToValue(), 'Range.toValue');
            } else {
                self::assertEquals($expected, $actual, $message);
            }
        } elseif (is_array($expected)) {
            self::assertEquals($expected, $actual, $message);
            foreach ($expected as $key => $expectedVal) {
                self::assertNormalizedValue($expectedVal, $actual[$key], $message . sprintf(' (Key: %s)', $key));
            }
        } else {
            self::assertSame($expected, $actual, $message);
            /**
             * do precise assertion for floats;
             * it is required due to {@see \PHPUnit\Framework\Constraint\IsIdentical::EPSILON}
             */
            if (is_float($expected) && is_float($actual)) {
                /** @noinspection PhpUnitTestsInspection */
                self::assertTrue(
                    $expected === $actual,
                    sprintf(
                        'Failed asserting that %s matches expected %s. Delta: %s. %s',
                        $actual,
                        $expected,
                        $expected - $actual,
                        $message
                    )
                );
            }
        }
    }

    public function testGetRequirementCache()
    {
        $processor = $this->getMockBuilder(NormalizeValueProcessor::class)
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'normalize_value'])
            ->onlyMethods(['process'])
            ->getMock();
        $processor->expects(self::exactly(4))
            ->method('process')
            ->willReturnCallback(function (NormalizeValueContext $context) {
                $context->setRequirement((string)$context->getRequestType());
            });

        $requestType1 = new RequestType([RequestType::REST]);
        $requestType2 = new RequestType([RequestType::JSON_API]);
        $valueNormalizer = new ValueNormalizer($processor);

        self::assertEquals((string)$requestType1, $valueNormalizer->getRequirement(DataType::INTEGER, $requestType1));
        self::assertEquals((string)$requestType2, $valueNormalizer->getRequirement(DataType::INTEGER, $requestType2));

        // test cached values
        self::assertEquals((string)$requestType1, $valueNormalizer->getRequirement(DataType::INTEGER, $requestType1));
        self::assertEquals((string)$requestType2, $valueNormalizer->getRequirement(DataType::INTEGER, $requestType2));

        // clear the memory cache
        $valueNormalizer->reset();

        // test that the memory cache was cleared
        self::assertEquals((string)$requestType1, $valueNormalizer->getRequirement(DataType::INTEGER, $requestType1));
        self::assertEquals((string)$requestType2, $valueNormalizer->getRequirement(DataType::INTEGER, $requestType2));
    }

    public function testNormalizeValueCache()
    {
        $processor = $this->getMockBuilder(NormalizeValueProcessor::class)
            ->setConstructorArgs([$this->createMock(ProcessorBagInterface::class), 'normalize_value'])
            ->onlyMethods(['process'])
            ->getMock();
        $processor->expects(self::exactly(4))
            ->method('process')
            ->willReturnCallback(function (NormalizeValueContext $context) {
                $context->setResult($context->getRequestType() . '_' . $context->getResult());
            });

        $requestType1 = new RequestType([RequestType::REST]);
        $requestType2 = new RequestType([RequestType::JSON_API]);
        $valueNormalizer = new ValueNormalizer($processor);

        self::assertEquals(
            $requestType1 . '_val',
            $valueNormalizer->normalizeValue('val', DataType::ENTITY_TYPE, $requestType1)
        );
        self::assertEquals(
            $requestType2 . '_val',
            $valueNormalizer->normalizeValue('val', DataType::ENTITY_TYPE, $requestType2)
        );

        // test cached values
        self::assertEquals(
            $requestType1 . '_val',
            $valueNormalizer->normalizeValue('val', DataType::ENTITY_TYPE, $requestType1)
        );
        self::assertEquals(
            $requestType2 . '_val',
            $valueNormalizer->normalizeValue('val', DataType::ENTITY_TYPE, $requestType2)
        );

        // clear the memory cache
        $valueNormalizer->reset();

        // test that the memory cache was cleared
        self::assertEquals(
            $requestType1 . '_val',
            $valueNormalizer->normalizeValue('val', DataType::ENTITY_TYPE, $requestType1)
        );
        self::assertEquals(
            $requestType2 . '_val',
            $valueNormalizer->normalizeValue('val', DataType::ENTITY_TYPE, $requestType2)
        );
    }
}
