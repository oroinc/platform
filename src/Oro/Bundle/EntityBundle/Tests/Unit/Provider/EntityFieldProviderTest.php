<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityFieldProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProviderMock */
    protected $entityConfigProvider;

    /** @var ConfigProviderMock */
    protected $extendConfigProvider;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityClassResolver;

    /** @var VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $virtualFieldProvider;

    /** @var VirtualRelationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $virtualRelationProvider;

    /** @var FieldTypeHelper */
    protected $fieldTypeHelper;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var EntityProvider */
    protected $entityProvider;

    /** @var EntityFieldProvider */
    protected $provider;

    /** @var ExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $exclusionProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    protected function setUp(): void
    {
        $configManager = $this->createMock(ConfigManager::class);
        $this->entityConfigProvider = new ConfigProviderMock($configManager, 'entity');
        $this->extendConfigProvider = new ConfigProviderMock($configManager, 'extend');

        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->entityClassResolver->expects(self::any())
            ->method('getEntityClass')
            ->willReturnCallback(static fn ($entityName) => str_replace(':', '\\Entity\\', $entityName));

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->exclusionProvider = $this->createMock(ExclusionProviderInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->entityProvider = new EntityProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $this->translator,
            $this->featureChecker
        );
        $this->entityProvider->setExclusionProvider($this->exclusionProvider);

        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn([]);
        $this->fieldTypeHelper = new FieldTypeHelper($entityExtendConfigurationProvider);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->virtualFieldProvider = $this->createMock(VirtualFieldProviderInterface::class);
        $this->virtualRelationProvider = $this->createMock(VirtualRelationProviderInterface::class);

        $this->provider = new EntityFieldProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $this->fieldTypeHelper,
            $this->doctrine,
            $this->translator,
            []
        );
        $this->provider->setEntityProvider($this->entityProvider);
        $this->provider->setVirtualFieldProvider($this->virtualFieldProvider);
        $this->provider->setVirtualRelationProvider($this->virtualRelationProvider);
        $this->provider->setExclusionProvider($this->exclusionProvider);
    }

    public function testGetFieldsNoEntityConfig(): void
    {
        $entityName = 'Acme:Test';
        $entityClassName = 'Acme\Entity\Test';

        $em = $this->createMock(EntityManager::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with($entityClassName)
            ->willReturn($em);

        $result = $this->provider->getEntityFields($entityName);
        self::assertEquals([], $result);
    }

    public function testGetFieldsWithDefaultParameters(): void
    {
        $config = [
            'Acme\Entity\Test' => [
                'config' => [
                    'label' => 'Test Label',
                    'plural_label' => 'Test Plural Label',
                    'icon' => 'fa-test',
                ],
                'fields' => [
                    'field1' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'C',
                        ],
                    ],
                    'field2' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'B',
                        ],
                    ],
                    'field3' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'A',
                        ],
                    ],
                    'field4' => [
                        'type' => 'string',
                        'config' => [],
                    ],
                ],
            ],
        ];
        $this->prepare($config);

        $result = $this->provider->getEntityFields('Acme:Test');
        $expected = [
            [
                'name' => 'field3',
                'type' => 'string',
                'label' => 'A Translated',
            ],
            [
                'name' => 'field4',
                'type' => 'string',
                'label' => 'acme.entity.test.field4.label Translated',
            ],
            [
                'name' => 'field2',
                'type' => 'string',
                'label' => 'B Translated',
            ],
            [
                'name' => 'field1',
                'type' => 'integer',
                'label' => 'C Translated',
                'identifier' => true,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider fieldsWithRelationsExpectedDataProvider
     */
    public function testGetFieldsWithRelations(array $expected): void
    {
        $this->prepareWithRelations();
        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEquals($expected, $result);
    }

    /**
     * exclusions are not used in workflow
     */
    public function fieldsWithRelationsExpectedDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-many',
                        'label' => 'Rel1 Translated',
                        'relation_type' => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldsWithRelationsAndDeepLevelDataProvider
     */
    public function testGetFieldsWithRelationsAndDeepLevel(array $expected): void
    {
        $this->prepareWithRelations();
        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEquals($expected, $result);
    }

    public function getFieldsWithRelationsAndDeepLevelDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-many',
                        'label' => 'Rel1 Translated',
                        'relation_type' => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldsWithRelationsAndDeepLevelAndEntityDetailsDataProvider
     */
    public function testGetFieldsWithRelationsAndDeepLevelAndEntityDetails(array $expected): void
    {
        $this->prepareWithRelations();
        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_ENTITY_DETAILS
            | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEquals($expected, $result);
    }

    public function getFieldsWithRelationsAndDeepLevelAndEntityDetailsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-many',
                        'label' => 'Rel1 Translated',
                        'relation_type' => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                        'related_entity_label' => 'Test1 Label Translated',
                        'related_entity_plural_label' => 'Test1 Plural Label Translated',
                        'related_entity_icon' => 'fa-test1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldsWithRelationsAndDeepLevelAndLastLevelRelations
     */
    public function testGetFieldsWithRelationsAndDeepLevelAndLastLevelRelations(array $expected): void
    {
        $this->prepareWithRelations();
        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEquals($expected, $result);
    }

    public function getFieldsWithRelationsAndDeepLevelAndLastLevelRelations(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-many',
                        'label' => 'Rel1 Translated',
                        'relation_type' => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldsWithRelationsAndDeepLevelAndLastLevelRelationsAndEntityDetailsDataProvider
     */
    public function testGetFieldsWithRelationsAndDeepLevelAndLastLevelRelationsAndEntityDetails(array $expected): void
    {
        $this->prepareWithRelations();
        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_ENTITY_DETAILS
            | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEquals($expected, $result);
    }

    public function getFieldsWithRelationsAndDeepLevelAndLastLevelRelationsAndEntityDetailsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-many',
                        'label' => 'Rel1 Translated',
                        'relation_type' => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                        'related_entity_label' => 'Test1 Label Translated',
                        'related_entity_plural_label' => 'Test1 Plural Label Translated',
                        'related_entity_icon' => 'fa-test1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldsWithRelationsAndDeepLevelAndWithUnidirectional
     */
    public function testGetFieldsWithRelationsAndDeepLevelAndWithUnidirectional(array $expected): void
    {
        $this->prepareWithRelations();

        $result = $this->provider->getEntityFields(
            'Acme:Test1',
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEquals($expected, $result);
    }

    public function getFieldsWithRelationsAndDeepLevelAndWithUnidirectional(): array
    {
        return [
            [
                [
                    [
                        'name' => 'Test1field2',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'id',
                        'type' => 'integer',
                        'label' => 'B Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-one',
                        'label' => 'Rel11 Translated',
                        'relation_type' => 'ref-one',
                        'related_entity_name' => 'Acme\Entity\Test11',
                    ],
                    [
                        'name' => 'Acme\Entity\Test22::uni_rel1',
                        'type' => 'ref-one',
                        'label' => 'UniRel1 Translated (Test22 Label Translated)',
                        'relation_type' => 'ref-one',
                        'related_entity_name' => 'Acme\Entity\Test22',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getFieldsWithVirtualRelationsAndEnumsDataProvider
     */
    public function testGetFieldsWithVirtualRelationsAndEnums(array $expected): void
    {
        $className = 'Acme\Entity\Test';

        $config = [
            $className => [
                'config' => [
                    'label' => 'Test Label',
                    'plural_label' => 'Test Plural Label',
                    'icon' => 'fa-test',
                ],
                'fields' => [
                    'field1' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'Field 1',
                        ],
                    ],
                ],
                'relations' => [
                    'rel1' => [
                        'target_class' => 'Acme\EnumValue1',
                        'type' => 'ref-one',
                        'config' => [
                            'label' => 'Enum Field',
                        ],
                    ],
                    'rel2' => [
                        'target_class' => 'Acme\EnumValue2',
                        'type' => 'ref-many',
                        'config' => [
                            'label' => 'Multi Enum Field',
                        ],
                    ],
                ],
            ],
        ];
        $this->prepare($config);

        $this->virtualFieldProvider->expects(self::once())
            ->method('getVirtualFields')
            ->with($className)
            ->willReturn(['rel1', 'rel2']);

        $this->virtualRelationProvider->expects(self::once())
            ->method('getVirtualRelations')
            ->with($className)
            ->willReturn(
                [
                    'virtual_relation' => [
                        'relation_type' => 'oneToMany',
                        'related_entity_name' => 'OtherEntity',
                        'query' => [
                            'select' => ['select expression'],
                            'join' => ['join expression'],
                        ],
                    ],
                ]
            );
        $this->virtualFieldProvider->expects(self::exactly(2))
            ->method('getVirtualFieldQuery')
            ->willReturnMap(
                [
                    [
                        $className,
                        'rel1',
                        [
                            'select' => [
                                'return_type' => 'enum',
                                'filter_by_id' => true,
                            ],
                        ],
                    ],
                    [
                        $className,
                        'rel2',
                        [
                            'select' => [
                                'return_type' => 'multiEnum',
                                'filter_by_id' => true,
                            ],
                        ],
                    ],
                ]
            );

        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        self::assertEqualsCanonicalizing($expected, $result);
    }

    public function getFieldsWithVirtualRelationsAndEnumsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'rel1',
                        'type' => 'enum',
                        'label' => 'Enum Field Translated',
                        'related_entity_name' => 'Acme\EnumValue1',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'Field 1 Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel2',
                        'type' => 'multiEnum',
                        'label' => 'Multi Enum Field Translated',
                        'related_entity_name' => 'Acme\EnumValue2',
                    ],
                    [
                        'name' => 'virtual_relation',
                        'type' => 'oneToMany',
                        'label' => 'acme.entity.test.virtual_relation.label Translated',
                        'relation_type' => 'oneToMany',
                        'related_entity_name' => 'OtherEntity',
                    ],
                ],
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepare(array $config): void
    {
        $metadata = [];
        foreach ($config as $entityClassName => $entityData) {
            $entityMetadata = $this->createMock(ClassMetadata::class);
            $entityMetadata->expects(self::any())
                ->method('getName')
                ->willReturn($entityClassName);
            $metadata[$entityClassName] = $entityMetadata;

            $fieldNames = [];
            $fieldTypes = [];
            $fieldIdentifiers = [];
            foreach ($entityData['fields'] as $fieldName => $fieldData) {
                $fieldNames[] = $fieldName;
                $fieldTypes[] = [$fieldName, $fieldData['type']];
                $fieldIdentifiers[] = [$fieldName, $fieldData['identifier'] ?? false];
            }
            $entityMetadata->expects(self::any())
                ->method('getFieldNames')
                ->willReturn($fieldNames);
            $entityMetadata->expects(self::any())
                ->method('hasField')
                ->willReturnCallback(static fn ($name) => in_array($name, $fieldNames, true));
            $entityMetadata->expects(self::any())
                ->method('isIdentifier')
                ->willReturnMap($fieldIdentifiers);

            $relNames = [];
            $mappings = [];
            if (isset($entityData['relations'])) {
                $relTargetClasses = [];
                foreach ($entityData['relations'] as $relName => $relData) {
                    $type = $relData['type'];
                    $relTargetClass = $relData['target_class'];
                    if ($type === 'ref-one') {
                        $mappings[$relName] = $relData;
                    }
                    $fieldTypes[] = [$relName, $type];
                    $relNames[] = $relName;
                    $relTargetClasses[] = [$relName, $relTargetClass];
                }
                $entityMetadata->expects(self::any())
                    ->method('getAssociationTargetClass')
                    ->willReturnMap($relTargetClasses);
                $entityMetadata->expects(self::any())
                    ->method('getAssociationMappedByTargetField')
                    ->willReturn('id');
            }
            $entityMetadata->expects(self::any())
                ->method('getAssociationNames')
                ->willReturn($relNames);
            $entityMetadata->expects(self::any())
                ->method('hasAssociation')
                ->willReturnCallback(static fn ($name) => in_array($name, $relNames, true));
            if (isset($entityData['unidirectional_relations'])) {
                foreach ($entityData['unidirectional_relations'] as $relName => $relData) {
                    $fieldTypes[] = [$relName, $relData['type']];
                    $relData['type'] = $relData['type'] !== 'ref-one' ?: ClassMetadataInfo::MANY_TO_ONE;
                    $relData['fieldName'] = $relName;
                    $relData['isOwningSide'] = true;
                    $relData['inversedBy'] = null;
                    $relData['sourceEntity'] = $entityClassName;
                    unset($relData['config']);
                    $mappings[$relName] = $relData;
                }
                $entityMetadata->expects(self::any())
                    ->method('getAssociationMappings')
                    ->willReturn($mappings);
            }
            $entityMetadata->expects(self::any())
                ->method('isSingleValuedAssociation')
                ->willReturnCallback(static fn ($field) => !empty($mappings[$field]));
            $entityMetadata->expects(self::any())
                ->method('getTypeOfField')
                ->willReturnMap($fieldTypes);
        }

        $em = $this->createMock(EntityManager::class);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $em->expects(self::any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects(self::any())
            ->method('getMetadataFor')
            ->willReturnCallback(static fn ($entityClassName) => $metadata[$entityClassName]);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(self::isType('string'))
            ->willReturn($em);

        foreach ($config as $entityClassName => $entityData) {
            if (isset($entityData['config'])) {
                $this->entityConfigProvider->addEntityConfig($entityClassName, $entityData['config']);
                $this->extendConfigProvider->addEntityConfig($entityClassName);
            }
            foreach (['fields', 'relations', 'unidirectional_relations'] as $fieldType) {
                if (isset($entityData[$fieldType])) {
                    foreach ($entityData[$fieldType] as $fieldName => $fieldData) {
                        if (isset($fieldData['config'])) {
                            $this->entityConfigProvider->addFieldConfig(
                                $entityClassName,
                                $fieldName,
                                $fieldData['type'],
                                $fieldData['config'],
                                $fieldData['hidden'] ?? false
                            );
                            $this->extendConfigProvider->addFieldConfig(
                                $entityClassName,
                                $fieldName,
                                $fieldData['type'],
                                [],
                                $fieldData['hidden'] ?? false
                            );
                        }
                    }
                }
            }
        }
        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn ($messageId) => $messageId . ' Translated');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareWithRelations(): void
    {
        $config = [
            'Acme\Entity\Test' => [
                'config' => [
                    'label' => 'Test Label',
                    'plural_label' => 'Test Plural Label',
                    'icon' => 'fa-test',
                ],
                'fields' => [
                    'field1' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'C',
                        ],
                    ],
                    'field2' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'B',
                        ],
                    ],
                    'field3' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'A',
                        ],
                    ],
                    'field4' => [
                        'type' => 'string',
                        'config' => [],
                    ],
                ],
                'relations' => [
                    'rel1' => [
                        'target_class' => 'Acme\Entity\Test1',
                        'type' => 'ref-many',
                        'config' => [
                            'label' => 'Rel1',
                        ],
                    ],
                ],
            ],
            'Acme\Entity\Test1' => [
                'config' => [
                    'label' => 'Test1 Label',
                    'plural_label' => 'Test1 Plural Label',
                    'icon' => 'fa-test1',
                ],
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'B',
                        ],
                    ],
                    'Test1field2' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'A',
                        ],
                    ],
                ],
                'relations' => [
                    'rel1' => [
                        'target_class' => 'Acme\Entity\Test11',
                        'type' => 'ref-one',
                        'config' => [
                            'label' => 'Rel11',
                        ],
                    ],
                ],
            ],
            'Acme\Entity\Test11' => [
                'config' => [
                    'label' => 'Test11 Label',
                    'plural_label' => 'Test11 Plural Label',
                    'icon' => 'fa-test11',
                ],
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'B',
                        ],
                    ],
                    'Test11field2' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'A',
                        ],
                    ],
                ],
                'relations' => [
                    'rel1' => [
                        'target_class' => 'Acme\Entity\Test111',
                        'type' => 'ref-one',
                        'config' => [
                            'label' => 'Rel111',
                        ],
                    ],
                ],
            ],
            'Acme\Entity\Test111' => [
                'config' => [
                    'label' => 'Test111 Label',
                    'plural_label' => 'Test111 Plural Label',
                    'icon' => 'fa-test111',
                ],
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'B',
                        ],
                    ],
                    'Test111field2' => [
                        'type' => 'string',
                        'config' => [
                            'label' => 'A',
                        ],
                    ],
                ],
            ],
            'Acme\Entity\Test22' => [
                'config' => [
                    'label' => 'Test22 Label',
                    'plural_label' => 'Test22 Plural Label',
                    'icon' => 'fa-test22',
                ],
                'fields' => [
                    'id' => [
                        'type' => 'integer',
                        'identifier' => true,
                        'config' => [
                            'label' => 'B',
                        ],
                    ],
                ],
                'unidirectional_relations' => [
                    'uni_rel1' => [
                        'targetEntity' => 'Acme\Entity\Test1',
                        'type' => 'ref-one',
                        'config' => [
                            'label' => 'UniRel1',
                        ],
                    ],
                ],
            ],
        ];
        $this->prepare($config);
    }

    /**
     * @dataProvider relationsExpectedDataProvider
     */
    public function testGetRelations(array $expected): void
    {
        $this->prepareWithRelations();
        $result = $this->provider->getRelations('Acme:Test', true);

        self::assertEquals($expected, $result);
    }

    /**
     * exclusions are not used in workflow
     */
    public function relationsExpectedDataProvider(): array
    {
        return [
            [
                [
                    'rel1' => [
                        'name' => 'rel1',
                        'type' => 'ref-many',
                        'label' => 'Rel1 Translated',
                        'relation_type' => 'ref-many',
                        'related_entity_name' => 'Acme\Entity\Test1',
                        'related_entity_label' => 'Test1 Label Translated',
                        'related_entity_plural_label' => 'Test1 Plural Label Translated',
                        'related_entity_icon' => 'fa-test1',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getTranslatedFieldsDataProvider
     */
    public function testGetTranslatedFields(
        int $translate,
        string $fieldLabel,
        string $fieldLabelTranslated,
        ?string $locale,
        int $transCalls
    ): void {
        $config = [
            'Acme\Entity\Test' => [
                'config' => [
                    'label' => 'Test Label',
                    'plural_label' => 'Test Plural Label',
                    'icon' => 'fa-test',
                ],
                'fields' => [
                    'field1' => [
                        'type' => 'string',
                        'config' => [
                            'label' => $fieldLabel,
                        ],
                    ],
                ],
            ],
        ];
        $this->translator->expects(self::exactly($transCalls))
            ->method('trans')
            ->with($fieldLabel, [], null, $locale)
            ->willReturn($fieldLabelTranslated);
        $this->prepare($config);

        $this->provider->setLocale($locale);
        $result = $this->provider->getEntityFields(
            'Acme:Test',
            EntityFieldProvider::OPTION_APPLY_EXCLUSIONS | $translate
        );
        $expected = [
            [
                'name' => 'field1',
                'type' => 'string',
                'label' => $fieldLabelTranslated,
            ],
        ];

        self::assertEquals($expected, $result);
    }

    public function getTranslatedFieldsDataProvider(): array
    {
        return [
            'translated' => [
                'translate' => EntityFieldProvider::OPTION_TRANSLATE,
                'fieldLabel' => 'C',
                'fieldLabelTranslated' => 'C Translated',
                'locale' => 'it_IT',
                'transCalls' => 1,
            ],
            'with translate = false' => [
                'translate' => 0,
                'fieldLabel' => 'C',
                'fieldLabelTranslated' => 'C',
                'locale' => null,
                'transCalls' => 0,
            ],
            'default translation' => [
                'translate' => EntityFieldProvider::OPTION_TRANSLATE,
                'fieldLabel' => 'C',
                'fieldLabelTranslated' => 'C Default',
                'locale' => null,
                'transCalls' => 1,
            ],
        ];
    }

    public function testGetLocale(): void
    {
        self::assertNull($this->provider->getLocale());

        $this->provider->setLocale('en-US');
        self::assertEquals('en-US', $this->provider->getLocale());
    }

    public function testGetHiddenFields(): void
    {
        $config = [
            'Acme\Entity\Test' => [
                'config' => [
                    'label' => 'Test Label',
                    'plural_label' => 'Test Plural Label',
                    'icon' => 'fa-test',
                ],
                'fields' => [
                    'field1' => [
                        'type' => 'string',
                        'config' => [],
                        'hidden' => true,
                    ],
                ],
            ],
        ];

        $this->prepare($config);

        $result = $this->provider->getEntityFields('Acme:Test', EntityFieldProvider::OPTION_WITH_HIDDEN_FIELDS);
        $expected = [
            [
                'name' => 'field1',
                'type' => 'string',
                'label' => 'acme.entity.test.field1.label',
            ],
        ];

        self::assertEquals($expected, $result);
    }
}
