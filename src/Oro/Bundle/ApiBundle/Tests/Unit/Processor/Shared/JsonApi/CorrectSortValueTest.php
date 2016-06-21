<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\CorrectSortValue;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class CorrectSortValueTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var CorrectSortValue */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new CorrectSortValue($this->doctrineHelper, $this->valueNormalizer);
    }

    /**
     * @param array $config
     *
     * @return EntityDefinitionConfig
     */
    protected function createConfigObject(array $config)
    {
        $configLoaderFactory = new ConfigLoaderFactory(new ConfigExtensionRegistry());

        return $configLoaderFactory->getLoader(ConfigUtil::DEFINITION)->load($config);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getQueryBuilderMock();

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($className, $config, $orderBy, $expectedOrderBy)
    {
        $sortFilterValue = new FilterValue('sort', $orderBy);
        $filterValueAccessor = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface');
        $filterValueAccessor->expects($this->once())
            ->method('get')
            ->with('sort')
            ->willReturn($sortFilterValue);

        if ($config) {
            $this->context->setConfig($this->createConfigObject($config));
        }
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertEquals($expectedOrderBy, $sortFilterValue->getValue());
    }

    public function processProvider()
    {
        return [
            'sort by identifier field (ASC)'                                                       => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                null,
                ['id' => 'ASC'],
                ['id' => 'ASC']
            ],
            'sort by identifier field (DESC)'                                                      => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                null,
                ['id' => 'DESC'],
                ['id' => 'DESC']
            ],
            'sort by several fields'                                                               => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                null,
                ['id' => 'ASC', 'label' => 'DESC'],
                ['id' => 'ASC', 'label' => 'DESC']
            ],
            'sort by "id" field when identifier field has different name'                          => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                null,
                ['id' => 'ASC'],
                ['name' => 'ASC']
            ],
            'sort by several fields including "id" field when identifier field has different name' => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                null,
                ['id' => 'DESC', 'label' => 'ASC'],
                ['name' => 'DESC', 'label' => 'ASC']
            ],
            'sort by renamed identifier field'                                                     => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
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
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
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
        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($defaultValue, DataType::ORDER_BY, $this->context->getRequestType(), false)
            ->willReturn($normalizedDefaultValue);
        $filterValueAccessor = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface');
        $filterValueAccessor->expects($this->once())
            ->method('set')
            ->willReturnCallback(
                function ($key, $value) use (&$sortFilterValue) {
                    $sortFilterValue = $value;
                }
            );

        if ($config) {
            $this->context->setConfig($this->createConfigObject($config));
        }
        $this->context->setFilterValues($filterValueAccessor);
        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertEquals($expectedOrderBy, $sortFilterValue->getValue());
    }

    public function processDefaultValueProvider()
    {
        return [
            'identifier field as default value'                                                => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                null,
                'id',
                ['id' => 'ASC'],
                ['id' => 'ASC']
            ],
            '"id" field as default value when identifier field has different name'             => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                null,
                'id',
                ['id' => 'ASC'],
                ['name' => 'ASC']
            ],
            '"id" field as default value when identifier field has different name and renamed' => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
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
        ];
    }

    public function testProcessNoDefaultValue()
    {
        $this->context->getFilters()->add(
            'sort',
            new SortFilter(DataType::ORDER_BY)
        );
        $this->context->setFilterValues($this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface'));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $this->assertNull($filterValues->get('sort'));
    }

    public function testProcessNoFilter()
    {
        $this->context->setFilterValues($this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface'));
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User');
        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $this->assertNull($filterValues->get('sort'));
    }
}
