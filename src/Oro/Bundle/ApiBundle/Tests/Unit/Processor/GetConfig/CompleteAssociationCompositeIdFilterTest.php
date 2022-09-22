<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteAssociationCompositeIdFilter;
use Oro\Bundle\ApiBundle\Request\DataType;

class CompleteAssociationCompositeIdFilterTest extends ConfigProcessorTestCase
{
    private const TEST_ASSOC_CLASS_NAME = 'test\associationEntity';

    private CompleteAssociationCompositeIdFilter $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new CompleteAssociationCompositeIdFilter();
    }

    public function testProcess(): void
    {
        $definition = $this->createEntityDefinitionConfig();
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        $filter = $filters->getField('association1');
        self::assertNotNull($filter);
        self::assertEquals(CompleteAssociationCompositeIdFilter::ASSOC_COMPOSITE_IDENTIFIER_TYPE, $filter->getType());
        self::assertEquals(DataType::STRING, $filter->getDataType());
        self::assertTrue($filter->isArrayAllowed());
    }

    public function testProcessWhenIdFilterConfigIsExcluded(): void
    {
        $definition = $this->createEntityDefinitionConfig(true);
        $definition->getField('association1')->setExcluded(true);
        $filters = new FiltersConfig();

        $this->context->setResult($definition);
        $this->context->setFilters($filters);
        $this->processor->process($this->context);

        $filter = $filters->getField('association1');
        self::assertNull($filter);
    }

    private function createEntityDefinitionConfig(): EntityDefinitionConfig
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields' => [
                'association1' => [
                    'targetClass' => self::TEST_ASSOC_CLASS_NAME,
                    'targetEntity' => $this->createConfigObject(
                        [
                            'exclusion_policy' => 'all',
                            'fields' => [
                                'id' => [
                                    'property_path' => 'id1'
                                ]
                            ],
                            'identifierFieldNames' => ['id1', 'id2']
                        ]
                    )
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $result */
        $result = $this->createConfigObject($config);
        return $result;
    }
}
