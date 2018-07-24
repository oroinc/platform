<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttributeManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const ENTITY_CLASS_NAME = 'entity_class_name';
    const ATTRIBUTE_FIELD_NAME = 'attribute_field_name';

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var Translator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var AttributeManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new AttributeManager(
            $this->configManager,
            $this->doctrineHelper,
            $this->translator
        );
    }

    /**
     * @param bool $isCheckSuccessful
     */
    private function expectsDatabaseCheck($isCheckSuccessful)
    {
        $this->configManager->expects($this->once())
            ->method('isDatabaseReadyToWork')
            ->willReturn($isCheckSuccessful);
    }

    /**
     * @return FieldConfigModelRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectsGetFieldConfigModelRepository()
    {
        $repository = $this->getMockBuilder(FieldConfigModelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(FieldConfigModel::class)
            ->willReturn($repository);

        return $repository;
    }
    /**
     * @return AttributeFamilyRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectsGetAttributeFamilyRepository()
    {
        $repository = $this->getMockBuilder(AttributeFamilyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AttributeFamily::class)
            ->willReturn($repository);

        return $repository;
    }

    public function testGetAttributesByGroup()
    {
        $this->expectsDatabaseCheck(true);

        $repository = $this->expectsGetFieldConfigModelRepository();
        $repository->expects($this->once())
            ->method('getAttributesByIds')
            ->with([1,2])
            ->willReturn([]);

        $group = new AttributeGroup();
        $group->addAttributeRelation($this->createAttributeGroupRelation(1));
        $group->addAttributeRelation($this->createAttributeGroupRelation(2));
        $this->manager->getAttributesByGroup($group);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     * @expectedExceptionMessage Cannot use config database when a db schema is not synced.
     */
    public function testGetAttributesByGroupWhenExceptionIsThrown()
    {
        $this->expectsDatabaseCheck(false);

        $this->manager->getAttributesByGroup(new AttributeGroup());
    }

    public function testGetAttributesByFamily()
    {
        $this->expectsDatabaseCheck(true);

        $repository = $this->expectsGetFieldConfigModelRepository();
        $repository->expects($this->once())
            ->method('getAttributesByIds')
            ->with([1,2,3])
            ->willReturn([]);

        $family = new AttributeFamily();
        $group1 = new AttributeGroup();
        $group1->addAttributeRelation($this->createAttributeGroupRelation(1));
        $group1->addAttributeRelation($this->createAttributeGroupRelation(2));
        $group2 = new AttributeGroup();
        $group2->addAttributeRelation($this->createAttributeGroupRelation(3));
        $family->addAttributeGroup($group1);
        $family->addAttributeGroup($group2);
        $this->manager->getAttributesByFamily($family);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     * @expectedExceptionMessage Cannot use config database when a db schema is not synced.
     */
    public function testGetAttributesByFamilyWhenExceptionIsThrown()
    {
        $this->expectsDatabaseCheck(false);

        $this->manager->getAttributesByFamily(new AttributeFamily());
    }

    public function testGetAttributesByClass()
    {
        $this->expectsDatabaseCheck(true);

        $repository = $this->expectsGetFieldConfigModelRepository();
        $repository->expects($this->once())
            ->method('getAttributesByClass')
            ->with('className')
            ->willReturn([]);

        $this->manager->getAttributesByClass('className');
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     * @expectedExceptionMessage Cannot use config database when a db schema is not synced.
     */
    public function testGetAttributesByClassWhenExceptionIsThrown()
    {
        $this->expectsDatabaseCheck(false);

        $this->manager->getAttributesByClass('ClassName');
    }

    public function testGetSystemAttributesByClass()
    {
        $this->expectsDatabaseCheck(true);

        $repository = $this->expectsGetFieldConfigModelRepository();
        $repository->expects($this->once())
            ->method('getAttributesByClassAndIsSystem')
            ->with('className', 1)
            ->willReturn([]);

        $this->manager->getSystemAttributesByClass('className');
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     * @expectedExceptionMessage Cannot use config database when a db schema is not synced.
     */
    public function testGetSystemAttributesByClassWhenExceptionIsThrown()
    {
        $this->expectsDatabaseCheck(false);

        $this->manager->getSystemAttributesByClass('ClassName');
    }

    public function testGetNonSystemAttributesByClass()
    {
        $this->expectsDatabaseCheck(true);

        $repository = $this->expectsGetFieldConfigModelRepository();
        $repository->expects($this->once())
            ->method('getAttributesByClassAndIsSystem')
            ->with('className', 0)
            ->willReturn([]);

        $this->manager->getNonSystemAttributesByClass('className');
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     * @expectedExceptionMessage Cannot use config database when a db schema is not synced.
     */
    public function testGetNonSystemAttributesByClassWhenExceptionIsThrown()
    {
        $this->expectsDatabaseCheck(false);

        $this->manager->getNonSystemAttributesByClass('ClassName');
    }

    public function testGetFamiliesByAttributeId()
    {
        $repository = $this->expectsGetAttributeFamilyRepository();
        $repository->expects($this->once())
            ->method('getFamiliesByAttributeId')
            ->with(1)
            ->willReturn([]);

        $this->manager->getFamiliesByAttributeId(1);
    }

    /**
     * @return array
     */
    public function isSystemDataProvider()
    {
        return [
           'system' => [
               true
           ],
           'not system' => [
               false
           ]
        ];
    }

    /**
     * @dataProvider isSystemDataProvider
     * @param bool $expectation
     */
    public function testIsSystem($expectation)
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('is')
            ->with('owner', ExtendScope::OWNER_SYSTEM)
            ->willReturn($expectation);

        $attributeFieldName = 'attributeFieldName';
        $entityClassName = 'entityClassName';

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
        $extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClassName, $attributeFieldName)
            ->willReturn($config);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        /** @var FieldConfigModel $attribute */
        $attribute = $this->getEntity(FieldConfigModel::class, [
            'fieldName' => $attributeFieldName,
            'entity' => $this->getEntity(EntityConfigModel::class, ['className' => $entityClassName])
        ]);

        $this->assertEquals($expectation, $this->manager->isSystem($attribute));
    }

    public function testAttributeLabelWhenTranslationExists()
    {
        $attributeLabel = 'oro.entity.attributeFieldName.label';
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn($attributeLabel);

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
        $entityConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME, self::ATTRIBUTE_FIELD_NAME)
            ->willReturn($config);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($entityConfigProvider);

        /** @var FieldConfigModel $attribute */
        $attribute = $this->getEntity(FieldConfigModel::class, [
            'fieldName' => self::ATTRIBUTE_FIELD_NAME,
            'entity' => $this->getEntity(EntityConfigModel::class, ['className' => self::ENTITY_CLASS_NAME])
        ]);

        $this->translator
            ->expects($this->once())
            ->method('hasTrans')
            ->with($attributeLabel)
            ->willReturn(true);

        $expectedTranslation = 'attribute label';
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with($attributeLabel)
            ->willReturn($expectedTranslation);

        $this->assertEquals($expectedTranslation, $this->manager->getAttributeLabel($attribute));
    }

    public function testAttributeLabelWhenTranslationNotExists()
    {
        $attributeLabel = 'oro.entity.attributeFieldName.label';
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn($attributeLabel);

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
        $entityConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME, self::ATTRIBUTE_FIELD_NAME)
            ->willReturn($config);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('entity')
            ->willReturn($entityConfigProvider);

        /** @var FieldConfigModel $attribute */
        $attribute = $this->getEntity(FieldConfigModel::class, [
            'fieldName' => self::ATTRIBUTE_FIELD_NAME,
            'entity' => $this->getEntity(EntityConfigModel::class, ['className' => self::ENTITY_CLASS_NAME])
        ]);

        $this->translator
            ->expects($this->once())
            ->method('hasTrans')
            ->with($attributeLabel)
            ->willReturn(false);

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->assertEquals(self::ATTRIBUTE_FIELD_NAME, $this->manager->getAttributeLabel($attribute));
    }

    public function testGetAttributesByIdsWithIndex()
    {
        $this->expectsDatabaseCheck(true);

        $repository = $this->expectsGetFieldConfigModelRepository();
        $repository->expects($this->once())
            ->method('getAttributesByIdsWithIndex')
            ->with([1, 2])
            ->willReturn([]);

        $this->manager->getAttributesByIdsWithIndex([1, 2]);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\LogicException
     * @expectedExceptionMessage Cannot use config database when a db schema is not synced.
     */
    public function testGetAttributesByIdsWithIndexWhenExceptionIsThrown()
    {
        $this->expectsDatabaseCheck(false);

        $this->manager->getAttributesByIdsWithIndex([1, 2]);
    }

    public function testGetAttributesMapByGroupIds()
    {
        $repository = $this->expectsGetAttributeGroupRelationRepository();
        $repository->expects($this->once())
            ->method('getAttributesMapByGroupIds')
            ->with([1, 2])
            ->willReturn([]);

        $this->manager->getAttributesMapByGroupIds([1, 2]);
    }

    /**
     * @return AttributeGroupRelationRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectsGetAttributeGroupRelationRepository()
    {
        $repository = $this->getMockBuilder(AttributeGroupRelationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AttributeGroupRelation::class)
            ->willReturn($repository);

        return $repository;
    }

    /**
     * @return array
     */
    public function groupsWithAttributesDataProvider()
    {
        $group1 = $this->getEntity(AttributeGroup::class, [
            'attributeRelations' => [
                $this->getEntity(AttributeGroupRelation::class, ['entityConfigFieldId' => 1]),
                $this->getEntity(AttributeGroupRelation::class, ['entityConfigFieldId' => 2])
            ]
        ]);
        $group2 = $this->getEntity(AttributeGroup::class, [
            'attributeRelations' => [
                $this->getEntity(AttributeGroupRelation::class, ['entityConfigFieldId' => 3])
            ]
        ]);
        $attribute1 = $this->getEntity(FieldConfigModel::class, ['id' => 1]);
        $attribute2 = $this->getEntity(FieldConfigModel::class, ['id' => 2]);
        $attribute3 = $this->getEntity(FieldConfigModel::class, ['id' => 3]);

        return [
            'empty' => [
                'groups' => [],
                'attributes' => [],
                'attributeIds' => [],
                'familyData' => [],
                'expectedData' => []
            ],
            'full' => [
                'groups' => [$group1, $group2],
                'attributes' => [
                    $attribute1->getId() => $attribute1,
                    $attribute2->getId() => $attribute2,
                    $attribute3->getId() => $attribute3
                ],
                'attributeIds' => [1, 2, 3],
                'familyData' => [
                    'attributeGroups' => [$group1, $group2]
                ],
                'expectedData' => [
                    [
                        'group' => $group1,
                        'attributes' => [
                            $attribute1,
                            $attribute2
                        ]
                    ],
                    [
                        'group' => $group2,
                        'attributes' => [
                            $attribute3
                        ]
                    ],
                ]
            ]
        ];
    }

    /**
     * @dataProvider groupsWithAttributesDataProvider
     * @param array $groups
     * @param array $attributes
     * @param array $attributeIds
     * @param array $familyData
     * @param array $expected
     */
    public function testGetGroupsWithAttributes(
        array $groups,
        array $attributes,
        array $attributeIds,
        array $familyData,
        array $expected
    ) {
        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->getEntity(AttributeFamily::class, $familyData);

        $groupRepository = $this->getMockBuilder(AttributeGroupRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldRepository = $this->getMockBuilder(FieldConfigModelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldRepository
            ->expects($this->once())
            ->method('getAttributesByIds')
            ->with($attributeIds)
            ->willReturn($attributes);

        $groupRepository
            ->expects($this->once())
            ->method('getGroupsWithAttributeRelations')
            ->with($attributeFamily)
            ->willReturn($groups);

        $this->expectsDatabaseCheck(true);

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getEntityRepositoryForClass')
            ->withConsecutive([AttributeGroup::class], [FieldConfigModel::class])
            ->willReturnOnConsecutiveCalls($groupRepository, $fieldRepository);

        $this->assertEquals($expected, $this->manager->getGroupsWithAttributes($attributeFamily));
    }

    /**
     * @param $attributeId
     * @return AttributeGroupRelation
     */
    private function createAttributeGroupRelation($attributeId)
    {
        return $this->getEntity(AttributeGroupRelation::class, ['entityConfigFieldId' => $attributeId]);
    }

    public function testIsActive()
    {
        $config = $this->createMock(ConfigInterface::class);
        $config
            ->expects($this->exactly(2))
            ->method('in')
            ->with('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE])
            ->willReturn(true);

        $attributeFieldName = 'attributeFieldName';
        $entityClassName = 'entityClassName';

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
        $extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfigProvider->expects($this->exactly(2))
            ->method('getConfig')
            ->with($entityClassName, $attributeFieldName)
            ->willReturn($config);

        $this->configManager
            ->expects($this->once())
            ->method('getProvider')
            ->with('extend')
            ->willReturn($extendConfigProvider);

        /** @var FieldConfigModel $attribute */
        $attribute = $this->getEntity(FieldConfigModel::class, [
            'fieldName' => $attributeFieldName,
            'entity' => $this->getEntity(EntityConfigModel::class, ['className' => $entityClassName])
        ]);

        $this->assertEquals(true, $this->manager->isActive($attribute));
        // Check that configManager::getProvider() called once
        $this->assertEquals(true, $this->manager->isActive($attribute));
    }
}
