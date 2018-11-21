<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
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

class CorrectSortValueTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var CorrectSortValue */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $filterNames = $this->createMock(FilterNames::class);
        $filterNames->expects(self::any())
            ->method('getSortFilterName')
            ->willReturn('sort');

        $this->processor = new CorrectSortValue(
            $this->doctrineHelper,
            $this->valueNormalizer,
            new FilterNamesRegistry([[$filterNames, null]], new RequestExpressionMatcher())
        );
    }

    /**
     * @param array $config
     *
     * @return EntityDefinitionConfig
     */
    private function createConfigObject(array $config)
    {
        $configLoaderFactory = new ConfigLoaderFactory(new ConfigExtensionRegistry());

        return $configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        self::assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        self::assertNull($this->context->getQuery());
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($className, $config, $orderBy, $expectedOrderBy)
    {
        $sortFilterValue = new FilterValue('sort', $orderBy);
        $filterValueAccessor = $this->createMock(FilterValueAccessorInterface::class);
        $filterValueAccessor->expects(self::once())
            ->method('get')
            ->with('sort')
            ->willReturn($sortFilterValue);

        $this->context->setConfig($this->createConfigObject($config ?? []));
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        self::assertEquals($expectedOrderBy, $sortFilterValue->getValue());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processProvider()
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
        $className,
        $config,
        $defaultValue,
        $normalizedDefaultValue,
        $expectedOrderBy
    ) {
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
            ->with($defaultValue, DataType::ORDER_BY, $this->context->getRequestType(), false)
            ->willReturn($normalizedDefaultValue);
        $filterValueAccessor = $this->createMock(FilterValueAccessorInterface::class);
        $filterValueAccessor->expects(self::once())
            ->method('set')
            ->willReturnCallback(
                function ($key, $value) use (&$sortFilterValue) {
                    $sortFilterValue = $value;
                }
            );

        $this->context->setConfig($this->createConfigObject($config ?? []));
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        self::assertEquals($expectedOrderBy, $sortFilterValue->getValue());
    }

    public function processDefaultValueProvider()
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

    public function testProcessNoDefaultValue()
    {
        $this->context->getFilters()->add(
            'sort',
            new SortFilter(DataType::ORDER_BY)
        );
        $this->context->setFilterValues($this->createMock(FilterValueAccessorInterface::class));
        $this->context->setClassName(Entity\User::class);
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        self::assertNull($filterValues->get('sort'));
    }

    public function testProcessNoFilter()
    {
        $this->context->setFilterValues($this->createMock(FilterValueAccessorInterface::class));
        $this->context->setClassName(Entity\User::class);
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        self::assertNull($filterValues->get('sort'));
    }
}
