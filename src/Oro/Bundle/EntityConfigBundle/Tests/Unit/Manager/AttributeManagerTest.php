<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS_NAME = 'entity_class_name';
    const ATTRIBUTE_FIELD_NAME = 'attribute_field_name';

    /**
     * @var ConfigModelManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configModelManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeConfigProvider;

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityConfigProvider;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var AttributeManager
     */
    protected $manager;

    protected function setUp()
    {
        $this->configModelManager = $this->getMockBuilder(ConfigModelManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new AttributeManager(
            $this->configModelManager,
            $this->doctrineHelper,
            $this->attributeConfigProvider,
            $this->entityConfigProvider,
            $this->translator
        );
    }

    /**
     * @param bool $isCheckSuccessful
     */
    private function expectsDatabaseCheck($isCheckSuccessful)
    {
        $this->configModelManager->expects($this->once())
            ->method('checkDatabase')
            ->willReturn($isCheckSuccessful);
    }

    /**
     * @return FieldConfigModelRepository|\PHPUnit_Framework_MockObject_MockObject
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
     * @return AttributeFamilyRepository|\PHPUnit_Framework_MockObject_MockObject
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
            [true],
            [false]
        ];
    }

    /**
     * @dataProvider isSystemDataProvider
     * @param bool $isSystem
     */
    public function testIsSystem($isSystem)
    {
        $config = $this->getMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('is')
            ->with('is_system')
            ->willReturn($isSystem);

        $attributeFieldName = 'attributeFieldName';
        $entityClassName = 'entityClassName';

        $this->attributeConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($entityClassName, $attributeFieldName)
            ->willReturn($config);
        /** @var FieldConfigModel $attribute */
        $attribute = $this->getEntity(FieldConfigModel::class, [
            'fieldName' => $attributeFieldName,
            'entity' => $this->getEntity(EntityConfigModel::class, ['className' => $entityClassName])
        ]);

        $this->assertEquals($isSystem, $this->manager->isSystem($attribute));
    }

    public function testAttributeLabelWhenTranslationExists()
    {
        $attributeLabel = 'oro.entity.attributeFieldName.label';
        $config = $this->getMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn($attributeLabel);

        $this->entityConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME, self::ATTRIBUTE_FIELD_NAME)
            ->willReturn($config);
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
        $config = $this->getMock(ConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn($attributeLabel);

        $this->entityConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS_NAME, self::ATTRIBUTE_FIELD_NAME)
            ->willReturn($config);

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
     * @return AttributeGroupRelationRepository|\PHPUnit_Framework_MockObject_MockObject
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
     * @param $attributeId
     * @return AttributeGroupRelation
     */
    private function createAttributeGroupRelation($attributeId)
    {
        return $this->getEntity(AttributeGroupRelation::class, ['entityConfigFieldId' => $attributeId]);
    }
}
