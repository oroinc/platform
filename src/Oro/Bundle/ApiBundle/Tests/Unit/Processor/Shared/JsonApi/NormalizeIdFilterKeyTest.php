<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\NormalizeIdFilterKey;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class NormalizeIdFilterKeyTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var NormalizeIdFilterKey */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new NormalizeIdFilterKey($this->doctrineHelper);
    }

    public function testProcessForNotManageableEntityWithoutIdentifier()
    {
        $className = 'Test\Class';
        $config = new EntityDefinitionConfig();

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntityWithIdentifier()
    {
        $className = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $filters = [
            'id'   => [
                'expectedKey'         => 'id',
                'expectedDescription' => 'Filter records by the identifier field'
            ],
            'name' => [
                'expectedKey'         => 'name',
                'expectedDescription' => null
            ]
        ];

        foreach (array_keys($filters) as $fieldName) {
            $filter = new ComparisonFilter('integer');
            $filter->setField($fieldName);
            $this->context->getFilters()->add($fieldName, $filter);
        }

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertFilters($filters, $this->context->getFilters());
    }

    public function testProcessForNotManageableEntityWithIdFilterAndAnotherFilterWithIdPropertyPathEqualsToId()
    {
        $className = 'Test\Class';
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['id']);
        $config->addField('id');

        $filters = [
            'id'       => [
                'expectedKey'         => 'id',
                'expectedDescription' => 'Filter records by the identifier field'
            ],
            'category' => [
                'property_path'       => 'id',
                'expectedKey'         => 'category',
                'expectedDescription' => null
            ]
        ];

        foreach ($filters as $fieldName => $item) {
            $filter = new ComparisonFilter('integer');
            $filter->setField($fieldName);
            if (isset($item['property_path'])) {
                $filter->setField($item['property_path']);
            }
            $this->context->getFilters()->add($fieldName, $filter);
        }

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        foreach ($this->context->getFilters() as $filterKey => $filterDefinition) {
            self::assertArrayHasKey($filterKey, $filters);
            $expectedFilter = $filters[$filterKey];
            self::assertEquals($expectedFilter['expectedKey'], $filterKey, $filterKey);
            self::assertEquals(
                $expectedFilter['expectedDescription'],
                $filterDefinition->getDescription(),
                'Description for ' . $filterKey
            );
        }
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcessForManageableEntityWithoutConfig(string $className, array $filters)
    {
        foreach (array_keys($filters) as $fieldName) {
            $filter = new ComparisonFilter('integer');
            $filter->setField($fieldName);
            $this->context->getFilters()->add($fieldName, $filter);
        }

        $this->context->setClassName($className);
        $this->context->setConfig(null);
        $this->processor->process($this->context);

        $this->assertFilters($filters, $this->context->getFilters());
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcessForManageableEntityWithConfig(string $className, array $filters)
    {
        $config = new EntityDefinitionConfig();
        $idFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($className);
        $config->setIdentifierFieldNames([$idFieldName]);
        $config->addField($idFieldName);

        foreach (array_keys($filters) as $fieldName) {
            $filter = new ComparisonFilter('integer');
            $filter->setField($fieldName);
            $this->context->getFilters()->add($fieldName, $filter);
        }

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertFilters($filters, $this->context->getFilters());
    }

    public function testProcessForManageableEntityWithRenamedIdentifier()
    {
        $className = Entity\User::class;
        $config = new EntityDefinitionConfig();
        $config->setIdentifierFieldNames(['renamedId']);
        $config->addField('renamedId')->setPropertyPath('id');

        $filters = [
            'id' => [
                'expectedKey'         => 'id',
                'expectedDescription' => 'Filter records by the identifier field'
            ]
        ];

        foreach (array_keys($filters) as $fieldName) {
            $filter = new ComparisonFilter('integer');
            $filter->setField($fieldName);
            $this->context->getFilters()->add($fieldName, $filter);
        }

        $this->context->setClassName($className);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        $this->assertFilters($filters, $this->context->getFilters());
    }

    public function processProvider(): array
    {
        return [
            [
                Entity\User::class,
                [
                    'id'   => [
                        'expectedKey'         => 'id',
                        'expectedDescription' => 'Filter records by the identifier field'
                    ],
                    'name' => [
                        'expectedKey'         => 'name',
                        'expectedDescription' => null
                    ]
                ]
            ],
            [
                Entity\Category::class,
                [
                    'name'  => [
                        'expectedKey'         => 'id',
                        'expectedDescription' => 'Filter records by the identifier field'
                    ],
                    'label' => [
                        'expectedKey'         => 'label',
                        'expectedDescription' => null
                    ]
                ]
            ]
        ];
    }

    private function assertFilters(array $expectedFilters, FilterCollection $actualFilters): void
    {
        foreach ($actualFilters as $filterKey => $filterDefinition) {
            $fieldName = $filterDefinition->getField();
            self::assertArrayHasKey($fieldName, $expectedFilters);
            $expectedFilter = $expectedFilters[$fieldName];
            self::assertEquals($expectedFilter['expectedKey'], $filterKey, $filterKey);
            self::assertEquals(
                $expectedFilter['expectedDescription'],
                $filterDefinition->getDescription(),
                'Description for ' . $filterKey
            );
        }
    }
}
