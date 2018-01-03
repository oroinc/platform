<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Placeholder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesImportFinishNotificationListener;
use Oro\Bundle\SyncBundle\Content\SimpleTagGenerator;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Placeholder\AttributesImportFilter;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributesImportFilterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_ALIAS = 'someAlias';
    const ENTITY_CLASS = 'someClass';
    const ENTITY_ID = 712;

    /**
     * @var EntityAliasResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityAliasResolver;

    /**
     * @var TagGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tagGenerator;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var AttributesImportFilter
     */
    protected $attributesImportFilter;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->getMockBuilder(EntityAliasResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tagGenerator = $this->createMock(TagGeneratorInterface::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributesImportFilter = new AttributesImportFilter($this->entityAliasResolver, $this->tagGenerator, $this->configManager);
    }

    /**
     * @dataProvider applicableAliasDataProvider
     * @param bool $hasAttributes
     * @param bool $isApplicable
     */
    public function testIsApplicableAlias(bool $hasAttributes, bool $isApplicable)
    {
        $this->entityAliasResolver
            ->expects($this->once())
            ->method('getClassByAlias')
            ->with(self::ENTITY_ALIAS)
            ->willReturn(self::ENTITY_CLASS);

        $entityConfig = new Config(new EntityConfigId('attribute'), [
            AttributeConfigHelper::CODE_HAS_ATTRIBUTES => $hasAttributes
        ]);

        $this->configManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with('attribute', self::ENTITY_CLASS)
            ->willReturn($entityConfig);

        $this->assertEquals($isApplicable, $this->attributesImportFilter->isApplicableAlias(self::ENTITY_ALIAS));
    }

    /**
     * @return array
     */
    public function applicableAliasDataProvider(): array
    {
        return [
            'alias is not applicable if respective entity class does not have attributes' => [
                'hasAttributes' => false,
                'isApplicable' => false
            ],
            'alias is applicable if respective entity class has attributes' => [
                'hasAttributes' => true,
                'isApplicable' => true
            ]
        ];
    }

    public function testIsApplicableEntityWhenNotEntityConfigModel()
    {
        $entity = new \stdClass();

        $this->assertFalse($this->attributesImportFilter->isApplicableEntity($entity));
    }

    /**
     * @dataProvider applicableEntityDataProvider
     * @param bool $hasAttributes
     * @param bool $isApplicable
     */
    public function testIsApplicableEntity(bool $hasAttributes, bool $isApplicable)
    {
        $entity = (new EntityConfigModel())->setClassName(self::ENTITY_CLASS);

        $entityConfig = new Config(new EntityConfigId('attribute'), [
            AttributeConfigHelper::CODE_HAS_ATTRIBUTES => $hasAttributes
        ]);

        $this->configManager
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with('attribute', self::ENTITY_CLASS)
            ->willReturn($entityConfig);

        $this->assertEquals($isApplicable, $this->attributesImportFilter->isApplicableEntity($entity));
    }

    /**
     * @return array
     */
    public function applicableEntityDataProvider(): array
    {
        return [
            'entity is not applicable if respective entity class does not have attributes' => [
                'hasAttributes' => false,
                'isApplicable' => false
            ],
            'entity is applicable if respective entity class has attributes' => [
                'hasAttributes' => true,
                'isApplicable' => true
            ]
        ];
    }

    public function testGetTagByAliasWhenNoEntityConfigModel()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No entity config model found for class ' . self::ENTITY_CLASS);

        $this->entityAliasResolver
            ->expects($this->once())
            ->method('getClassByAlias')
            ->with(self::ENTITY_ALIAS)
            ->willReturn(self::ENTITY_CLASS);

        $this->configManager
            ->expects($this->once())
            ->method('getConfigEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $this->attributesImportFilter->getTagByAlias(self::ENTITY_ALIAS);
    }

    public function testGetTagByAlias()
    {
        $this->entityAliasResolver
            ->expects($this->once())
            ->method('getClassByAlias')
            ->with(self::ENTITY_ALIAS)
            ->willReturn(self::ENTITY_CLASS);

        $entityConfigModel = $this->getEntity(EntityConfigModel::class, ['id' => self::ENTITY_ID]);
        $this->configManager
            ->expects($this->once())
            ->method('getConfigEntityModel')
            ->with(self::ENTITY_CLASS)
            ->willReturn($entityConfigModel);

        $tagData = [['tag1']];
        $this->tagGenerator
            ->expects($this->once())
            ->method('generate')
            ->with([
                SimpleTagGenerator::STATIC_NAME_KEY
                => AttributesImportFinishNotificationListener::ATTRIBUTE_IMPORT_FINISH_TAG,
                SimpleTagGenerator::IDENTIFIER_KEY => [self::ENTITY_ID]
            ])
            ->willReturn($tagData);

        $this->assertEquals($tagData, $this->attributesImportFilter->getTagByAlias(self::ENTITY_ALIAS));
    }

    public function testGetTagByEntityWhenNotEntityConfigModel()
    {
        $tagData = [['tag1']];
        $this->tagGenerator
            ->expects($this->once())
            ->method('generate')
            ->with([
                SimpleTagGenerator::STATIC_NAME_KEY
                => AttributesImportFinishNotificationListener::ATTRIBUTE_IMPORT_FINISH_TAG,
                SimpleTagGenerator::IDENTIFIER_KEY => [self::ENTITY_ID]
            ])
            ->willReturn($tagData);

        /** @var EntityConfigModel $entity */
        $entity = $this->getEntity(EntityConfigModel::class, ['id' => self::ENTITY_ID]);

        $this->assertEquals($tagData, $this->attributesImportFilter->getTagByEntity($entity));
    }
}
