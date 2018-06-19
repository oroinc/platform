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

    protected function setUp()
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

    /**
     * @dataProvider processProvider
     */
    public function testProcessForManageableEntityWithoutConfig($className, $filters)
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
    public function testProcessForManageableEntityWithConfig($className, $filters)
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
            'id'   => [
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

    public function processProvider()
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

    /**
     * @param array            $expectedFilters
     * @param FilterCollection $actualFilters
     */
    private function assertFilters(array $expectedFilters, FilterCollection $actualFilters)
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
