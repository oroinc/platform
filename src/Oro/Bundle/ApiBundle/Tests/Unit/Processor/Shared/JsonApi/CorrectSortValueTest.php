<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Filter\FilterNames;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\CorrectSortValue;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;

class CorrectSortValueTest extends GetListProcessorOrmRelatedTestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private FilterNames&MockObject $filterNames;
    private CorrectSortValue $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->filterNames = $this->createMock(FilterNames::class);

        $this->processor = new CorrectSortValue(
            $this->doctrineHelper,
            $this->valueNormalizer,
            new FilterNamesRegistry(
                [['filter_names', null]],
                TestContainerBuilder::create()->add('filter_names', $this->filterNames)->getContainer($this),
                new RequestExpressionMatcher()
            )
        );
    }

    private function createConfigObject(array $config): EntityDefinitionConfig
    {
        $configLoaderFactory = new ConfigLoaderFactory(new ConfigExtensionRegistry());

        return $configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }

    public function testProcessOnExistingQuery(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity(): void
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    public function testProcessWhenSortingIsNotSupported(): void
    {
        $filterValueAccessor = $this->createMock(FilterValueAccessorInterface::class);
        $filterValueAccessor->expects(self::never())
            ->method(self::anything());

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn(null);

        $this->context->setConfig($this->createConfigObject([]));
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName(Entity\User::class);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess(string $className, ?array $config, array $orderBy, array $expectedOrderBy): void
    {
        $sortFilterValue = new FilterValue('sort', $orderBy);
        $filterValueAccessor = $this->createMock(FilterValueAccessorInterface::class);
        $filterValueAccessor->expects(self::once())
            ->method('getOne')
            ->with('sort')
            ->willReturn($sortFilterValue);

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setConfig($this->createConfigObject($config ?? []));
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        self::assertEquals($expectedOrderBy, $sortFilterValue->getValue());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processProvider(): array
    {
        return [
            'sort by identifier field (ASC)'                                                       => [
                Entity\User::class,
                null,
                ['id' => 'ASC'],
                ['id' => 'ASC']
            ],
            'sort by identifier field (DESC)'                                                      => [
                Entity\User::class,
                null,
                ['id' => 'DESC'],
                ['id' => 'DESC']
            ],
            'sort by several fields'                                                               => [
                Entity\User::class,
                null,
                ['id' => 'ASC', 'label' => 'DESC'],
                ['id' => 'ASC', 'label' => 'DESC']
            ],
            'sort by "id" field when identifier field has different name'                          => [
                Entity\Category::class,
                null,
                ['id' => 'ASC'],
                ['name' => 'ASC']
            ],
            'sort by several fields including "id" field when identifier field has different name' => [
                Entity\Category::class,
                null,
                ['id' => 'DESC', 'label' => 'ASC'],
                ['name' => 'DESC', 'label' => 'ASC']
            ],
            'sort by renamed identifier field'                                                     => [
                Entity\User::class,
                [
                    'fields' => [
                        'renamedId' => [
                            'property_path' => 'id'
                        ]
                    ]
                ],
                ['id' => 'ASC'],
                ['renamedId' => 'ASC']
            ],
            'sort by "id" field when identifier field has different name and renamed'              => [
                Entity\Category::class,
                [
                    'fields' => [
                        'renamedId' => [
                            'property_path' => 'name'
                        ]
                    ]
                ],
                ['id' => 'ASC'],
                ['renamedId' => 'ASC']
            ],
            'sort by "id" field when identifier fields exist in config (ASC)'                      => [
                Entity\Category::class,
                [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'property_path' => 'name'
                        ]
                    ]
                ],
                ['id' => 'ASC'],
                ['id' => 'ASC']
            ],
            'sort by "id" field when identifier fields exist in config (DESC)'                     => [
                Entity\Category::class,
                [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'property_path' => 'name'
                        ]
                    ]
                ],
                ['id' => 'DESC'],
                ['id' => 'DESC']
            ],
            'sort by "id" field for composite identifier (ASC)'                                    => [
                Entity\Category::class,
                [
                    'identifier_field_names' => ['id', 'label'],
                    'fields'                 => [
                        'id'    => [
                            'property_path' => 'name'
                        ],
                        'label' => null
                    ]
                ],
                ['id' => 'ASC'],
                ['id' => 'ASC', 'label' => 'ASC']
            ],
            'sort by "id" field for composite identifier (DESC)'                                   => [
                Entity\Category::class,
                [
                    'identifier_field_names' => ['id', 'label'],
                    'fields'                 => [
                        'id'    => [
                            'property_path' => 'name'
                        ],
                        'label' => null
                    ]
                ],
                ['id' => 'DESC'],
                ['id' => 'DESC', 'label' => 'DESC']
            ]
        ];
    }

    /**
     * @dataProvider processDefaultValueProvider
     */
    public function testProcessDefaultValue(
        string $className,
        ?array $config,
        string $defaultValue,
        array $normalizedDefaultValue,
        array $expectedOrderBy
    ): void {
        $sortFilterValue = null;

        $this->context->getFilters()->add(
            'sort',
            new SortFilter(
                DataType::ORDER_BY,
                '',
                null,
                function () use ($defaultValue) {
                    return $defaultValue;
                }
            )
        );
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($defaultValue, DataType::ORDER_BY, $this->context->getRequestType())
            ->willReturn($normalizedDefaultValue);
        $filterValueAccessor = $this->createMock(FilterValueAccessorInterface::class);
        $filterValueAccessor->expects(self::once())
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$sortFilterValue) {
                $sortFilterValue = $value;
            });

        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setConfig($this->createConfigObject($config ?? []));
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        self::assertEquals($expectedOrderBy, $sortFilterValue->getValue());
    }

    public function processDefaultValueProvider(): array
    {
        return [
            'identifier field as default value'                                                => [
                Entity\User::class,
                null,
                'id',
                ['id' => 'ASC'],
                ['id' => 'ASC']
            ],
            '"id" field as default value when identifier field has different name'             => [
                Entity\Category::class,
                null,
                'id',
                ['id' => 'ASC'],
                ['name' => 'ASC']
            ],
            '"id" field as default value when identifier field has different name and renamed' => [
                Entity\Category::class,
                [
                    'fields' => [
                        'renamedId' => [
                            'property_path' => 'name'
                        ]
                    ]
                ],
                'id',
                ['id' => 'ASC'],
                ['renamedId' => 'ASC']
            ],
            '"id" field as default value when identifier fields exist in config'               => [
                Entity\Category::class,
                [
                    'identifier_field_names' => ['id'],
                    'fields'                 => [
                        'id' => [
                            'property_path' => 'name'
                        ]
                    ]
                ],
                'id',
                ['id' => 'ASC'],
                ['id' => 'ASC']
            ]
        ];
    }

    public function testProcessNoDefaultValue(): void
    {
        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->getFilters()->add(
            'sort',
            new SortFilter(DataType::ORDER_BY)
        );
        $this->context->setClassName(Entity\User::class);
        $this->processor->process($this->context);

        self::assertNull($this->context->getFilterValues()->getOne('sort'));
    }

    public function testProcessNoFilter(): void
    {
        $this->filterNames->expects(self::once())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->context->setClassName(Entity\User::class);
        $this->processor->process($this->context);

        self::assertNull($this->context->getFilterValues()->getOne('sort'));
    }
}
