<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeParentResourceHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class MergeParentResourceHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var ConfigContext */
    private $context;

    /** @var MergeParentResourceHelper */
    private $mergeParentResourceHelper;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->mergeParentResourceHelper = new MergeParentResourceHelper(
            $this->configProvider
        );

        $this->context = new ConfigContext();
        $this->context->setVersion('1.2');
        $this->context->getRequestType()->add('rest');
        $this->context->setExtras([new TestConfigSection('test')]);
    }

    /**
     * @param string $parentResourceClass
     * @param Config $parentConfig
     */
    private function loadParentConfig($parentResourceClass, Config $parentConfig)
    {
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentResourceClass,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getExtras()
            )
            ->willReturn($parentConfig);
    }

    public function testMergeEmptyParentConfig()
    {
        $parentResourceClass = 'Test\ParentEntity';

        $this->loadParentConfig($parentResourceClass, new Config());
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);
    }

    public function testMergeParentDefinitionWhenEntityDefinitionIsEmpty()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentDefinition = new EntityDefinitionConfig();
        $parentConfig->setDefinition($parentDefinition);

        $parentDefinition->setExcludeAll();

        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentDefinition()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentDefinition = new EntityDefinitionConfig();
        $parentConfig->setDefinition($parentDefinition);

        $parentDefinition->setExcludeAll();
        $parentDefinition->setKey('parent key');
        $parentDefinition->setDescription('parent entity');

        $definition = new EntityDefinitionConfig();
        $definition->setKey('key');
        $definition->setDescription('entity');

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertEquals('key', $parentDefinition->getKey());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'description'           => 'entity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentDefinitionFields()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentDefinition = new EntityDefinitionConfig();
        $parentConfig->setDefinition($parentDefinition);

        $parentDefinition->setExcludeAll();
        $parentDefinition->addField('field1')->setExcluded();
        $parentDefinition->addField('field2');
        $parentDefinition->addField('field3');
        $parentAssociation1Target = $parentDefinition->addField('association1')->createAndSetTargetEntity();
        $parentAssociation1Target->setKey('parent association1 target key');
        $parentAssociation1Target->addField('association1field1');
        $parentDefinition->addField('association2')->createAndSetTargetEntity()
            ->setExcludeAll();
        $parentDefinition->addField('association3')->createAndSetTargetEntity()
            ->setExcludeAll();
        $parentDefinition->addField('fieldToAssociation1');
        $parentDefinition->addField('fieldToAssociation2')->createAndSetTargetEntity()
            ->addField('fieldToAssociation2Field1');

        $definition = new EntityDefinitionConfig();
        $definition->addField('field1')->setPropertyPath('field1propertyPath');
        $definition->addField('field3')->setExcluded();
        $definition->addField('field4');
        $definition->addField('association1')->createAndSetTargetEntity()
            ->addField('association1field2');
        $definition->addField('association2')->createAndSetTargetEntity()
            ->setExcludeNone();
        $definition->addField('association4')->createAndSetTargetEntity()
            ->setExcludeAll();
        $definition->addField('fieldToAssociation1')->createAndSetTargetEntity()
            ->addField('fieldToAssociation1Field1');
        $definition->addField('fieldToAssociation2')->setExcluded();

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'fields'                => [
                    'field1'              => [
                        'exclude'       => true,
                        'property_path' => 'field1propertyPath'
                    ],
                    'field2'              => null,
                    'field3'              => [
                        'exclude' => true
                    ],
                    'field4'              => null,
                    'association1'        => [
                        'fields' => [
                            'association1field1' => null,
                            'association1field2' => null
                        ]
                    ],
                    'association2'        => null,
                    'association3'        => [
                        'exclusion_policy' => 'all'
                    ],
                    'association4'        => [
                        'exclusion_policy' => 'all'
                    ],
                    'fieldToAssociation1' => [
                        'fields' => [
                            'fieldToAssociation1Field1' => null
                        ]
                    ],
                    'fieldToAssociation2' => [
                        'exclude' => true,
                        'fields'  => [
                            'fieldToAssociation2Field1' => null
                        ]
                    ]
                ]
            ],
            $parentDefinition->toArray()
        );
        self::assertNull($parentDefinition->getField('association1')->getTargetEntity()->getKey());
    }

    public function testMergeParentFiltersWhenEntityFiltersAreEmpty()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentFilters = new FiltersConfig();
        $parentConfig->setFilters($parentFilters);

        $parentFilters->setExcludeAll();
        $parentFilters->addField('filter1');

        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentFilters, $this->context->get('filters'));
        self::assertEquals(
            [
                'fields' => [
                    'filter1' => null
                ]
            ],
            $parentFilters->toArray()
        );
    }

    public function testMergeParentFilters()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentFilters = new FiltersConfig();
        $parentConfig->setFilters($parentFilters);

        $parentFilters->setExcludeAll();
        $parentFilters->addField('filter1')->setPropertyPath('parentField1');
        $parentFilters->addField('filter2')->setPropertyPath('parentField2');
        $parentFilters->addField('filter3');

        $filters = new FiltersConfig();
        $filters->addField('filter1')->setPropertyPath('field1');
        $filters->addField('filter2')->setExcluded();
        $filters->addField('filter4');

        $this->context->set('filters', $filters);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentFilters, $this->context->get('filters'));
        self::assertEquals(
            [
                'fields' => [
                    'filter1' => [
                        'property_path' => 'field1'
                    ],
                    'filter2' => [
                        'exclude'       => true,
                        'property_path' => 'parentField2'
                    ],
                    'filter3' => null,
                    'filter4' => null
                ]
            ],
            $parentFilters->toArray()
        );
    }

    public function testMergeParentSortersWhenEntitySortersAreEmpty()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentSorters = new SortersConfig();
        $parentConfig->setSorters($parentSorters);

        $parentSorters->setExcludeAll();
        $parentSorters->addField('sorter1');

        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentSorters, $this->context->get('sorters'));
        self::assertEquals(
            [
                'fields' => [
                    'sorter1' => null
                ]
            ],
            $parentSorters->toArray()
        );
    }

    public function testMergeParentSorters()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentSorters = new SortersConfig();
        $parentConfig->setSorters($parentSorters);

        $parentSorters->setExcludeAll();
        $parentSorters->addField('sorter1')->setPropertyPath('parentField1');
        $parentSorters->addField('sorter2')->setPropertyPath('parentField2');
        $parentSorters->addField('sorter3');

        $sorters = new SortersConfig();
        $sorters->addField('sorter1')->setPropertyPath('field1');
        $sorters->addField('sorter2')->setExcluded();
        $sorters->addField('sorter4');

        $this->context->set('sorters', $sorters);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentSorters, $this->context->get('sorters'));
        self::assertEquals(
            [
                'fields' => [
                    'sorter1' => [
                        'property_path' => 'field1'
                    ],
                    'sorter2' => [
                        'exclude'       => true,
                        'property_path' => 'parentField2'
                    ],
                    'sorter3' => null,
                    'sorter4' => null
                ]
            ],
            $parentSorters->toArray()
        );
    }

    public function testMergeParentActionsWhenEntityActionsAreEmpty()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentActions = new ActionsConfig();
        $parentConfig->setActions($parentActions);

        $parentActions->addAction('action1')->setExcluded();

        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertFalse($this->context->has('actions'));
    }

    public function testMergeParentActions()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentActions = new ActionsConfig();
        $parentConfig->setActions($parentActions);

        $parentActions->addAction('action1')->setDescription('parent description 1');

        $actions = new ActionsConfig();
        $actions->addAction('action1')->setDescription('description 1');
        $actions->addAction('action2')->setDescription('description 2');

        $this->context->set('actions', $actions);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($actions, $this->context->get('actions'));
        self::assertEquals(
            [
                'action1' => [
                    'description' => 'description 1'
                ],
                'action2' => [
                    'description' => 'description 2'
                ]
            ],
            $actions->toArray()
        );
    }

    public function testMergeParentSubresourcesWhenEntitySubresourcesAreEmpty()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentSubresources = new SubresourcesConfig();
        $parentConfig->setSubresources($parentSubresources);

        $parentSubresources->addSubresource('subresource1')->setExcluded();

        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertFalse($this->context->has('subresources'));
    }

    public function testMergeParentSubresources()
    {
        $parentResourceClass = 'Test\ParentEntity';
        $parentConfig = new Config();
        $parentSubresources = new SubresourcesConfig();
        $parentConfig->setSubresources($parentSubresources);

        $parentSubresources->addSubresource('subresource1')->setTargetClass('parent target 1');

        $subresources = new SubresourcesConfig();
        $subresources->addSubresource('subresource1')->setTargetClass('target 1');
        $subresources->addSubresource('subresource2')->setTargetClass('target 2');

        $this->context->set('subresources', $subresources);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($subresources, $this->context->get('subresources'));
        self::assertEquals(
            [
                'subresource1' => [
                    'target_class' => 'target 1'
                ],
                'subresource2' => [
                    'target_class' => 'target 2'
                ]
            ],
            $subresources->toArray()
        );
    }
}
