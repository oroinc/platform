<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasConfigBag;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendEntityAliasProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendEntityAliasProviderTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private DuplicateEntityAliasResolver&MockObject $duplicateResolver;
    private ExtendEntityAliasProvider $entityAliasProvider;

    #[\Override]
    protected function setUp(): void
    {
        $configProvider = $this->createMock(EntityConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturnMap([
                [
                    EntityConfiguration::ENTITY_ALIASES,
                    [
                        'Test\EntityWithCustomAlias'          => [
                            'alias'        => 'my_alias',
                            'plural_alias' => 'my_plural_alias'
                        ],
                        'Extend\Entity\EntityWithCustomAlias' => [
                            'alias'        => 'my_alias_custom_entity',
                            'plural_alias' => 'my_plural_alias_custom_entity'
                        ],
                        'Extend\Entity\EV_EntityWithCustomAlias' => [
                            'alias'        => 'my_alias_custom_enum',
                            'plural_alias' => 'my_plural_alias_custom_enum'
                        ]
                    ]
                ],
                [
                    EntityConfiguration::ENTITY_ALIAS_EXCLUSIONS,
                    [
                        'Test\ExcludedEntity',
                        'Extend\Entity\ExcludedEntity',
                        'Extend\Entity\EV_ExcludedEntity'
                    ]
                ]
            ]);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->duplicateResolver = $this->createMock(DuplicateEntityAliasResolver::class);

        $this->entityAliasProvider = new ExtendEntityAliasProvider(
            new EntityAliasConfigBag($configProvider),
            $this->configManager,
            $this->duplicateResolver,
            (new InflectorFactory())->build()
        );
    }

    public function testGetEntityAliasForNotConfigurableEntity(): void
    {
        $entityClass = 'Test\Entity';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(false);

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertNull($result);
    }

    public function testGetEntityAliasForEnum(): void
    {
        $entityClass = 'Extend\Entity\EV_Test_Enum';
        $expectedAlias = new EntityAlias('testenum', 'testenums');

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
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForEnumWhenItHasAliasInEntityConfig(): void
    {
        $entityClass = 'Extend\Entity\EV_Test_Enum';
        $expectedAlias = new EntityAlias('testenum', 'testenums');

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
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForEnumWithDuplicatedAlias(): void
    {
        $entityClass = 'Extend\Entity\EV_Test_Enum';
        $defaultAlias = 'testenum';
        $defaultPluralAlias = 'testenums';
        $expectedAlias = new EntityAlias('testenum1', 'testenum1');

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
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForEnumWithCustomAlias(): void
    {
        $entityClass = 'Extend\Entity\EV_EntityWithCustomAlias';
        $expectedAlias = new EntityAlias('my_alias_custom_enum', 'my_plural_alias_custom_enum');

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForExcludedEnum(): void
    {
        $entityClass = 'Extend\Entity\EV_ExcludedEntity';

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider dictionaryDataProvider
     */
    public function testGetEntityAliasForDictionary(string $entityClass, ?string $expectedAlias): void
    {
        $enumConfig = new Config(new EntityConfigId('enum', $entityClass));
        $groupingConfig = new Config(
            new EntityConfigId('grouping', $entityClass),
            ['groups' => ['dictionary']]
        );

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $entityClass)
            ->willReturn(new Config(new EntityConfigId('grouping', $entityClass), ['groups' => ['dictionary']]));

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertEquals($expectedAlias, $result);
    }

    public function dictionaryDataProvider(): array
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

    public function testGetEntityAliasForCustomEntity(): void
    {
        $entityClass = 'Extend\Entity\User';
        $expectedAlias = new EntityAlias('extenduser', 'extendusers');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $entityClass)
            ->willReturn(new Config(new EntityConfigId('grouping', $entityClass)));

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
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForCustomEntityWhenItHasAliasInEntityConfig(): void
    {
        $entityClass = 'Extend\Entity\User';
        $entityAlias = new EntityAlias('extenduser', 'extendusers');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $entityClass)
            ->willReturn(new Config(new EntityConfigId('grouping', $entityClass)));
        $this->duplicateResolver->expects(self::once())
            ->method('getAlias')
            ->with($entityClass)
            ->willReturn($entityAlias);
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertEquals($entityAlias, $result);
    }

    public function testGetEntityAliasForCustomEntityWithDuplicatedAlias(): void
    {
        $entityClass = 'Extend\Entity\User';
        $defaultAlias = 'extenduser';
        $defaultPluralAlias = 'extendusers';
        $expectedAlias = new EntityAlias('extenduser1', 'extenduser1');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $entityClass)
            ->willReturn(new Config(new EntityConfigId('grouping', $entityClass)));

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
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForCustomEntityWithCustomAlias(): void
    {
        $entityClass = 'Extend\Entity\EntityWithCustomAlias';
        $expectedAlias = new EntityAlias('my_alias_custom_entity', 'my_plural_alias_custom_entity');

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $entityClass)
            ->willReturn(new Config(new EntityConfigId('grouping', $entityClass)));

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertEquals($expectedAlias, $result);
    }

    public function testGetEntityAliasForExcludedCustomEntity(): void
    {
        $entityClass = 'Extend\Entity\ExcludedEntity';

        $this->configManager->expects(self::once())
            ->method('hasConfig')
            ->with($entityClass)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('grouping', $entityClass)
            ->willReturn(new Config(new EntityConfigId('grouping', $entityClass)));

        $this->duplicateResolver->expects(self::never())
            ->method('getAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('hasAlias');
        $this->duplicateResolver->expects(self::never())
            ->method('saveAlias');

        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        self::assertFalse($result);
    }
}
