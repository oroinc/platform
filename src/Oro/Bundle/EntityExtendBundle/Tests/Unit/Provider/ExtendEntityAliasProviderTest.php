<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasConfigBag;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendEntityAliasProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ExtendEntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityAliasConfigBag */
    private $entityAliasConfigBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DuplicateEntityAliasResolver */
    private $duplicateResolver;

    /** @var ExtendEntityAliasProvider */
    private $entityAliasProvider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
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
        $this->duplicateResolver = $this->createMock(DuplicateEntityAliasResolver::class);

        $this->entityAliasProvider = new ExtendEntityAliasProvider(
            $this->entityAliasConfigBag,
            $this->configManager,
            $this->duplicateResolver
        );
    }

    private function assertEntityAlias($expected, $actual)
    {
        if ($expected instanceof EntityAlias) {
            self::assertEquals($expected, $actual);
        } else {
            self::assertSame($expected, $actual);
        }
    }

    public function testGetEntityAliasForNotConfigurableEntity()
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertNull($result);
    }

    public function testGetEntityAliasForEnum()
    {
        $entityClass = 'Test\Entity';
        $expectedAlias = new EntityAlias('testenum', 'testenums');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass), ['code' => 'test_enum']);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $entityClass)
            ->willReturn($enumConfig);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($expectedAlias->getAlias(), $expectedAlias->getPluralAlias())
            ->willReturn(false);
        $this->duplicateResolver->expects(self::never())
            ->method('getUniqueAlias');
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($entityClass, $expectedAlias);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForEnumWhenItHasAliasInEntityConfig()
    {
        $entityClass = 'Test\Entity';
        $expectedAlias = new EntityAlias('testenum', 'testenums');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass), ['code' => 'test_enum']);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $entityClass)
            ->willReturn($enumConfig);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn($expectedAlias);
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('getUniqueAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForEnumWithDuplicatedAlias()
    {
        $entityClass = 'Test\Entity';
        $defaultAlias = 'testenum';
        $defaultPluralAlias = 'testenums';
        $expectedAlias = new EntityAlias('testenum1', 'testenum1');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass), ['code' => 'test_enum']);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $entityClass)
            ->willReturn($enumConfig);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn(true);
        $this->duplicateResolver->expects(self::once())
            ->method('getUniqueAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn($expectedAlias->getAlias());
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($entityClass, $expectedAlias);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForEnumWithCustomAlias()
    {
        $entityClass = 'Test\EntityWithCustomAlias';
        $expectedAlias = new EntityAlias('my_alias', 'my_plural_alias');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass), ['code' => 'test_enum']);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $entityClass)
            ->willReturn($enumConfig);

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForExcludedEnum()
    {
        $entityClass = 'Test\ExcludedEntity';
        $expectedAlias = false;

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass), ['code' => 'test_enum']);

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('enum', $entityClass)
            ->willReturn($enumConfig);

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    /**
     * @dataProvider dictionaryDataProvider
     */
    public function testGetEntityAliasForDictionary($entityClass, $expectedAlias)
    {
        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(
            new EntityConfigId('grouping', $entityClass),
            ['groups' => [GroupingScope::GROUP_DICTIONARY]]
        );

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['enum', $entityClass, $enumConfig],
                ['grouping', $entityClass, $groupingConfig]
            ]);

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

    public function testGetEntityAliasForCustomEntity()
    {
        $entityClass = ExtendHelper::ENTITY_NAMESPACE . 'User';
        $expectedAlias = new EntityAlias('extenduser', 'extendusers');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['enum', $entityClass, $enumConfig],
                ['grouping', $entityClass, $groupingConfig]
            ]);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($expectedAlias->getAlias(), $expectedAlias->getPluralAlias())
            ->willReturn(false);
        $this->duplicateResolver->expects(self::never())
            ->method('getUniqueAlias');
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($entityClass, $expectedAlias);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForCustomEntityWhenItHasAliasInEntityConfig()
    {
        $entityClass = ExtendHelper::ENTITY_NAMESPACE . 'User';
        $entityAlias = new EntityAlias('extenduser', 'extendusers');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['enum', $entityClass, $enumConfig],
                ['grouping', $entityClass, $groupingConfig]
            ]);
        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn($entityAlias);
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($entityAlias, $result);
    }

    public function testGetEntityAliasForCustomEntityWithDuplicatedAlias()
    {
        $entityClass = ExtendHelper::ENTITY_NAMESPACE . 'User';
        $defaultAlias = 'extenduser';
        $defaultPluralAlias = 'extendusers';
        $expectedAlias = new EntityAlias('extenduser1', 'extenduser1');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['enum', $entityClass, $enumConfig],
                ['grouping', $entityClass, $groupingConfig]
            ]);

        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn(null);
        $this->duplicateResolver->expects(self::once())
            ->method('hasAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn(true);
        $this->duplicateResolver->expects(self::once())
            ->method('getUniqueAlias')
            ->with($defaultAlias, $defaultPluralAlias)
            ->willReturn($expectedAlias->getAlias());
        $this->duplicateResolver->expects(self::once())
            ->method('saveAlias')
            ->with($entityClass, $expectedAlias);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForCustomEntityWithCustomAlias()
    {
        $entityClass = ExtendHelper::ENTITY_NAMESPACE . 'EntityWithCustomAlias';
        $expectedAlias = new EntityAlias('my_alias_custom_entity', 'my_plural_alias_custom_entity');

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['enum', $entityClass, $enumConfig],
                ['grouping', $entityClass, $groupingConfig]
            ]);

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function testGetEntityAliasForExcludedCustomEntity()
    {
        $entityClass = ExtendHelper::ENTITY_NAMESPACE . 'ExcludedEntity';
        $expectedAlias = false;

        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(new EntityConfigId('grouping', $entityClass));

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->willReturnMap([
                ['enum', $entityClass, $enumConfig],
                ['grouping', $entityClass, $groupingConfig]
            ]);

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }
}
