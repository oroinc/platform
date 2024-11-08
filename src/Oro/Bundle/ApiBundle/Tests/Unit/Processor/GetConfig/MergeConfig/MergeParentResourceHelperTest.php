<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeParentResourceHelper;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class MergeParentResourceHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ConfigContext */
    private $context;

    /** @var MergeParentResourceHelper */
    private $mergeParentResourceHelper;

    protected function setUp(): void
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

    private function loadParentConfig(string $parentResourceClass, Config $parentConfig): void
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

    private function getConfig(EntityDefinitionConfig $definition): Config
    {
        $config = new Config();
        $config->setDefinition($definition);

        return $config;
    }

    public function testMergeEmptyParentConfig(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $this->loadParentConfig($parentResourceClass, new Config());
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);
    }

    public function testMergeParentDefinitionWhenEntityDefinitionIsEmpty(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->setExcludeAll();

        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentDefinition(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->setExcludeAll();
        $parentDefinition->setKey('parent key');
        $parentDefinition->setDescription('parent entity');

        $definition = new EntityDefinitionConfig();
        $definition->setKey('key');
        $definition->setDescription('entity');

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertFalse($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertEquals('key', $parentDefinition->getKey());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'description'           => 'entity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentDefinitionWhenEntityHasAnotherIdentifierField(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->setIdentifierFieldNames(['id']);

        $definition = new EntityDefinitionConfig();
        $parentDefinition->setIdentifierFieldNames(['anotherId']);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'identifier_field_names' => ['anotherId']
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenItIsDisabledForParentEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setEnabled(false);

        $definition = new EntityDefinitionConfig();

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertFalse($parentDefinition->getUpsertConfig()->isEnabled());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenItIsEnabledForParentEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setEnabled(true);

        $definition = new EntityDefinitionConfig();

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertTrue($parentDefinition->getUpsertConfig()->isEnabled());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenItIsDisabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setEnabled(false);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertFalse($parentDefinition->getUpsertConfig()->isEnabled());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenItIsEnabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setEnabled(true);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertTrue($parentDefinition->getUpsertConfig()->isEnabled());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenItIsDisabledForParentEntityAndEnabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setEnabled(false);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setEnabled(true);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertTrue($parentDefinition->getUpsertConfig()->isEnabled());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenItIsEnabledForParentEntityAndDisabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setEnabled(true);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setEnabled(false);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasEnabled());
        self::assertFalse($parentDefinition->getUpsertConfig()->isEnabled());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenAllowedByIdIsDisabledForParentEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setAllowedById(false);

        $definition = new EntityDefinitionConfig();

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasAllowedById());
        self::assertFalse($parentDefinition->getUpsertConfig()->isAllowedById());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenAllowedByIdIsEnabledForParentEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setAllowedById(true);

        $definition = new EntityDefinitionConfig();

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasAllowedById());
        self::assertTrue($parentDefinition->getUpsertConfig()->isAllowedById());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['id']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenAllowedByIdIsDisabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(false);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasAllowedById());
        self::assertFalse($parentDefinition->getUpsertConfig()->isAllowedById());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenAllowedByIdIsEnabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(true);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasAllowedById());
        self::assertTrue($parentDefinition->getUpsertConfig()->isAllowedById());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['id']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenAllowedByIdIsDisabledForParentEntityAndEnabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setAllowedById(false);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(true);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasAllowedById());
        self::assertTrue($parentDefinition->getUpsertConfig()->isAllowedById());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['id']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigWhenAllowedByIdIsEnabledForParentEntityAndDisabledForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->setAllowedById(true);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->setAllowedById(false);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->hasAllowedById());
        self::assertFalse($parentDefinition->getUpsertConfig()->isAllowedById());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity'
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigFields(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->addFields(['field1']);
        $parentDefinition->getUpsertConfig()->addFields(['field2', 'field3']);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->addFields(['field4']);
        $definition->getUpsertConfig()->addFields(['field3', 'field2']);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertFalse($parentDefinition->getUpsertConfig()->isReplaceFields());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['field1'], ['field2', 'field3'], ['field4']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigFieldsWhenTheyAreReplacedForParentEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->replaceFields([['field1'], ['field2', 'field3']]);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->addFields(['field4']);
        $definition->getUpsertConfig()->addFields(['field3', 'field2']);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->isReplaceFields());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['field1'], ['field2', 'field3'], ['field4']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigFieldsWhenTheyAreReplacedForEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->addFields(['field1']);
        $parentDefinition->getUpsertConfig()->addFields(['field2', 'field3']);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->replaceFields([['field4'], ['field3', 'field2']]);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->isReplaceFields());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['field4'], ['field2', 'field3']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentUpsertConfigFieldsWhenTheyAreReplacedForBothParentEntityAndEntity(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->getUpsertConfig()->replaceFields([['field1'], ['field2', 'field3']]);

        $definition = new EntityDefinitionConfig();
        $definition->getUpsertConfig()->replaceFields([['field4'], ['field3', 'field2']]);

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertTrue($parentDefinition->getUpsertConfig()->isReplaceFields());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'upsert'                => [['field4'], ['field2', 'field3']]
            ],
            $parentDefinition->toArray()
        );
    }

    public function testMergeParentDefinitionFields(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
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
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
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

    public function testMergeParentDefinitionRenamedFields(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentDefinition = new EntityDefinitionConfig();
        $parentDefinition->setExcludeAll();
        $parentDefinition->addField('field1');
        $parentAssociation1Target = $parentDefinition->addField('association1')->createAndSetTargetEntity();
        $parentAssociation1Target->setKey('parent association1 target key');
        $parentAssociation1Target->addField('association1field1');

        $definition = new EntityDefinitionConfig();
        $definition->addField('renamedField1')->setPropertyPath('field1');
        $renamedAssociation1 = $definition->addField('renamedAssociation1');
        $renamedAssociation1->setPropertyPath('association1');
        $renamedAssociation1->createAndSetTargetEntity()->addField('association1field1');

        $this->context->setResult($definition);
        $this->loadParentConfig($parentResourceClass, $this->getConfig($parentDefinition));
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentDefinition, $this->context->getResult());
        self::assertEquals(
            [
                'parent_resource_class' => 'Test\ParentEntity',
                'fields' => [
                    'renamedField1' => [
                        'property_path' => 'field1'
                    ],
                    'renamedAssociation1' => [
                        'property_path' => 'association1',
                        'fields' => [
                            'association1field1' => null
                        ]
                    ]
                ]
            ],
            $parentDefinition->toArray()
        );
        self::assertNull($parentDefinition->getField('renamedAssociation1')->getTargetEntity()->getKey());
    }

    public function testMergeParentFiltersWhenEntityFiltersAreEmpty(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentFilters = new FiltersConfig();
        $parentFilters->setExcludeAll();
        $parentFilters->addField('filter1');

        $parentConfig = new Config();
        $parentConfig->setFilters($parentFilters);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentFilters, $this->context->getFilters());
        self::assertEquals(
            [
                'fields' => [
                    'filter1' => null
                ]
            ],
            $parentFilters->toArray()
        );
    }

    public function testMergeParentFilters(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentFilters = new FiltersConfig();
        $parentFilters->setExcludeAll();
        $parentFilters->addField('filter1')->setPropertyPath('parentField1');
        $parentFilters->addField('filter2')->setPropertyPath('parentField2');
        $parentFilters->addField('filter3');

        $filters = new FiltersConfig();
        $filters->addField('filter1')->setPropertyPath('field1');
        $filters->addField('filter2')->setExcluded();
        $filters->addField('filter4');

        $this->context->setFilters($filters);
        $parentConfig = new Config();
        $parentConfig->setFilters($parentFilters);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentFilters, $this->context->getFilters());
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

    public function testMergeParentSortersWhenEntitySortersAreEmpty(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentSorters = new SortersConfig();
        $parentSorters->setExcludeAll();
        $parentSorters->addField('sorter1');

        $parentConfig = new Config();
        $parentConfig->setSorters($parentSorters);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentSorters, $this->context->getSorters());
        self::assertEquals(
            [
                'fields' => [
                    'sorter1' => null
                ]
            ],
            $parentSorters->toArray()
        );
    }

    public function testMergeParentSorters(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentSorters = new SortersConfig();
        $parentSorters->setExcludeAll();
        $parentSorters->addField('sorter1')->setPropertyPath('parentField1');
        $parentSorters->addField('sorter2')->setPropertyPath('parentField2');
        $parentSorters->addField('sorter3');

        $sorters = new SortersConfig();
        $sorters->addField('sorter1')->setPropertyPath('field1');
        $sorters->addField('sorter2')->setExcluded();
        $sorters->addField('sorter4');

        $this->context->setSorters($sorters);
        $parentConfig = new Config();
        $parentConfig->setSorters($parentSorters);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertSame($parentSorters, $this->context->getSorters());
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

    public function testMergeParentActionsWhenEntityActionsAreEmpty(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentActions = new ActionsConfig();
        $parentActions->addAction('action1')->setExcluded();

        $parentConfig = new Config();
        $parentConfig->setActions($parentActions);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertFalse($this->context->has('actions'));
    }

    public function testMergeParentActions(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentActions = new ActionsConfig();
        $parentActions->addAction('action1')->setDescription('parent description 1');

        $actions = new ActionsConfig();
        $actions->addAction('action1')->setDescription('description 1');
        $actions->addAction('action2')->setDescription('description 2');

        $this->context->set('actions', $actions);
        $parentConfig = new Config();
        $parentConfig->setActions($parentActions);
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

    public function testMergeParentSubresourcesWhenEntitySubresourcesAreEmpty(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentSubresources = new SubresourcesConfig();
        $parentSubresources->addSubresource('subresource1')->setExcluded();

        $parentConfig = new Config();
        $parentConfig->setSubresources($parentSubresources);
        $this->loadParentConfig($parentResourceClass, $parentConfig);
        $this->mergeParentResourceHelper->mergeParentResourceConfig($this->context, $parentResourceClass);

        self::assertFalse($this->context->has('subresources'));
    }

    public function testMergeParentSubresources(): void
    {
        $parentResourceClass = 'Test\ParentEntity';

        $parentSubresources = new SubresourcesConfig();
        $parentSubresources->addSubresource('subresource1')->setTargetClass('parent target 1');

        $subresources = new SubresourcesConfig();
        $subresources->addSubresource('subresource1')->setTargetClass('target 1');
        $subresources->addSubresource('subresource2')->setTargetClass('target 2');

        $this->context->set('subresources', $subresources);
        $parentConfig = new Config();
        $parentConfig->setSubresources($parentSubresources);
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
