<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasConfigBag;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendEntityAliasProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendEntityAliasProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAliasConfigBag */
    protected $entityAliasConfigBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ExtendEntityAliasProvider */
    protected $entityAliasProvider;

    protected function setUp()
    {
        $this->configManager        = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityAliasConfigBag = new EntityAliasConfigBag(
            [
                'Test\EntityWithCustomAlias'                             => [
                    'alias'        => 'my_alias',
                    'plural_alias' => 'my_plural_alias'
                ],
                ExtendHelper::ENTITY_NAMESPACE . 'EntityWithCustomAlias' => [
                    'alias'        => 'my_alias_custom_entity',
                    'plural_alias' => 'my_plural_alias_custom_entity'
                ]
            ],
            [
                'Test\ExcludedEntity',
                ExtendHelper::ENTITY_NAMESPACE . 'ExcludedEntity'
            ]
        );
        $this->entityAliasProvider  = new ExtendEntityAliasProvider(
            $this->entityAliasConfigBag,
            $this->configManager
        );
    }

    public function testGetEntityAliasForNotConfigurableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertNull($result);
    }

    /**
     * @dataProvider enumDataProvider
     */
    public function testGetEntityAliasForEnum($entityClass, $expectedAlias)
    {
        $enumConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $enumConfig         = new Config(new EntityConfigId('enum', $entityClass));
        $enumConfig->set('code', 'test_enum');

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('enum')
            ->willReturn($enumConfigProvider);
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($enumConfig);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function enumDataProvider()
    {
        return [
            'enum'                   => [
                'entityClass'   => 'Test\Entity',
                'expectedAlias' => new EntityAlias('testenum', 'testenums')
            ],
            'enum_with_custom_alias' => [
                'entityClass'   => 'Test\EntityWithCustomAlias',
                'expectedAlias' => new EntityAlias('my_alias', 'my_plural_alias')
            ],
            'enum_excluded'          => [
                'entityClass'   => 'Test\ExcludedEntity',
                'expectedAlias' => false
            ]
        ];
    }

    /**
     * @dataProvider dictionaryDataProvider
     */
    public function testGetEntityAliasForDictionary($entityClass, $expectedAlias)
    {
        $enumConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $enumConfig         = new Config(new EntityConfigId('enum', $entityClass));

        $groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $groupingConfig         = new Config(new EntityConfigId('grouping', $entityClass));
        $groupingConfig->set('groups', [GroupingScope::GROUP_DICTIONARY]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['enum', $enumConfigProvider],
                    ['grouping', $groupingConfigProvider]
                ]
            );
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($enumConfig);
        $groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($groupingConfig);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function dictionaryDataProvider()
    {
        return [
            'dictionary'                   => [
                'entityClass'   => 'Test\Entity',
                'expectedAlias' => null
            ],
            'dictionary_with_custom_alias' => [
                'entityClass'   => 'Test\EntityWithCustomAlias',
                'expectedAlias' => null
            ],
            'dictionary_excluded'          => [
                'entityClass'   => 'Test\ExcludedEntity',
                'expectedAlias' => null
            ]
        ];
    }

    /**
     * @dataProvider hiddenEntityDataProvider
     */
    public function testGetEntityAliasForHiddenEntity($entityClass, $expectedAlias)
    {
        $enumConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $enumConfig         = new Config(new EntityConfigId('enum', $entityClass));

        $groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $groupingConfig         = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['enum', $enumConfigProvider],
                    ['grouping', $groupingConfigProvider]
                ]
            );
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($enumConfig);
        $groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($groupingConfig);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function hiddenEntityDataProvider()
    {
        return [
            'hidden'                   => [
                'entityClass'   => 'Test\Entity',
                'expectedAlias' => false
            ],
            'hidden_with_custom_alias' => [
                'entityClass'   => 'Test\EntityWithCustomAlias',
                'expectedAlias' => false
            ],
            'hidden_excluded'          => [
                'entityClass'   => 'Test\ExcludedEntity',
                'expectedAlias' => false
            ]
        ];
    }

    /**
     * @dataProvider customEntityDataProvider
     */
    public function testGetEntityAliasForCustomEntity($entityClass, $expectedAlias)
    {
        $enumConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $enumConfig         = new Config(new EntityConfigId('enum', $entityClass));

        $groupingConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $groupingConfig         = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('isHiddenModel')
            ->with($entityClass)
            ->willReturn(false);
        $this->configManager->expects($this->exactly(2))
            ->method('getProvider')
            ->willReturnMap(
                [
                    ['enum', $enumConfigProvider],
                    ['grouping', $groupingConfigProvider]
                ]
            );
        $enumConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($enumConfig);
        $groupingConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityClass)
            ->willReturn($groupingConfig);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function customEntityDataProvider()
    {
        return [
            'custom'                   => [
                'entityClass'   => ExtendHelper::ENTITY_NAMESPACE . 'User',
                'expectedAlias' => new EntityAlias('extenduser', 'extendusers')
            ],
            'custom_with_custom_alias' => [
                'entityClass'   => ExtendHelper::ENTITY_NAMESPACE . 'EntityWithCustomAlias',
                'expectedAlias' => new EntityAlias(
                    'my_alias_custom_entity',
                    'my_plural_alias_custom_entity'
                )
            ],
            'custom_excluded'          => [
                'entityClass'   => ExtendHelper::ENTITY_NAMESPACE . 'ExcludedEntity',
                'expectedAlias' => false
            ]
        ];
    }

    protected function assertEntityAlias($expected, $actual)
    {
        if ($expected instanceof EntityAlias) {
            $this->assertEquals($expected, $actual);
        } else {
            $this->assertSame($expected, $actual);
        }
    }
}
