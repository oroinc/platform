<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Placeholder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesImportFinishNotificationListener;
use Oro\Bundle\EntityConfigBundle\Placeholder\AttributesImportFilter;
use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;
use Oro\Bundle\SyncBundle\Content\SimpleTagGenerator;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributesImportFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const ENTITY_ALIAS = 'someAlias';
    const ENTITY_CLASS = 'someClass';
    const ENTITY_ID = 712;
    const TOPIC = 'Topic';

    /**
     * @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityAliasResolver;

    /**
     * @var AttributesImportTopicSender|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $topicSender;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configManager;

    /**
     * @var AttributesImportFilter
     */
    protected $attributesImportFilter;

    protected function setUp()
    {
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->topicSender = $this->createMock(AttributesImportTopicSender::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->attributesImportFilter = new AttributesImportFilter(
            $this->entityAliasResolver,
            $this->topicSender,
            $this->configManager
        );
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

    public function testGetTopicByAliasWhenNoEntityConfigModel()
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

        $this->topicSender
            ->expects($this->never())
            ->method('getTopic');

        $this->attributesImportFilter->getTopicByAlias(self::ENTITY_ALIAS);
    }

    public function testGetTopicByAlias()
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

        $this->topicSender
            ->expects($this->once())
            ->method('getTopic')
            ->with(self::ENTITY_ID)
            ->willReturn(self::TOPIC);

        $this->assertEquals(
            ['topic' => self::TOPIC],
            $this->attributesImportFilter->getTopicByAlias(self::ENTITY_ALIAS)
        );
    }

    public function testGetTopicByEntity()
    {
        $this->topicSender
            ->expects($this->once())
            ->method('getTopic')
            ->with(self::ENTITY_ID)
            ->willReturn(self::TOPIC);

        /** @var EntityConfigModel $entity */
        $entity = $this->getEntity(EntityConfigModel::class, ['id' => self::ENTITY_ID]);

        $this->assertEquals(['topic' => self::TOPIC], $this->attributesImportFilter->getTopicByEntity($entity));
    }
}
