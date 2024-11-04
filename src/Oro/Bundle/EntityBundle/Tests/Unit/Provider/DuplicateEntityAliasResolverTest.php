<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DuplicateEntityAliasResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DuplicateEntityAliasResolver */
    private $duplicateResolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->duplicateResolver = new DuplicateEntityAliasResolver($this->configManager);
    }

    private function expectInitializeAliases(): void
    {
        $config1 = new Config(
            new EntityConfigId('entity', 'Test\Entity1'),
            ['entity_alias' => 'entity1_alias', 'entity_plural_alias' => 'entity1_plural_alias']
        );
        $config2 = new Config(
            new EntityConfigId('entity', 'Test\Entity2'),
            []
        );
        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('entity', null, true)
            ->willReturn([$config1, $config2]);
        $this->configManager->expects(self::exactly(2))
            ->method('getIds')
            ->with('enum', self::isType('string'), true)
            ->willReturnCallback(function (string $scope, string $entityClass) {
                $result = [new FieldConfigId($scope, $entityClass, 'id', 'integer')];
                if ('Test\Entity2' === $entityClass) {
                    $result[] = new FieldConfigId($scope, $entityClass, 'enumField1', 'enum');
                    $result[] = new FieldConfigId($scope, $entityClass, 'enumField2', 'enum');
                }

                return $result;
            });
        $this->configManager->expects(self::atLeast(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                [
                    'enum',
                    'Test\Entity2',
                    'enumField1',
                    new Config(
                        new FieldConfigId('enum', 'Test\Entity2', 'enumField1', 'enum'),
                        [
                            'enum_code'           => 'enum_1',
                            'entity_alias'        => 'enum1_alias',
                            'entity_plural_alias' => 'enum1_plural_alias'
                        ]
                    )
                ],
                [
                    'enum',
                    'Test\Entity2',
                    'enumField2',
                    new Config(
                        new FieldConfigId('enum', 'Test\Entity2', 'enumField2', 'enum'),
                        ['enum_code' => 'enum_2']
                    )
                ]
            ]);
    }

    public function testGetAlias(): void
    {
        $this->expectInitializeAliases();

        self::assertEquals(
            new EntityAlias('entity1_alias', 'entity1_plural_alias'),
            $this->duplicateResolver->getAlias('Test\Entity1')
        );
        self::assertNull(
            $this->duplicateResolver->getAlias('Test\Entity2')
        );
        self::assertNull(
            $this->duplicateResolver->getAlias('Test\Entity3')
        );
    }

    public function testGetAliasForEnumEntity(): void
    {
        $this->expectInitializeAliases();

        self::assertEquals(
            new EntityAlias('enum1_alias', 'enum1_plural_alias'),
            $this->duplicateResolver->getAlias('Extend\Entity\EV_Enum_1')
        );
        self::assertNull(
            $this->duplicateResolver->getAlias('Extend\Entity\EV_Enum_2')
        );
        self::assertNull(
            $this->duplicateResolver->getAlias('Extend\Entity\EV_Enum_3')
        );
    }

    public function testHasAlias(): void
    {
        $this->expectInitializeAliases();

        self::assertTrue(
            $this->duplicateResolver->hasAlias('entity1_alias', 'entity1_plural_alias')
        );
        self::assertTrue(
            $this->duplicateResolver->hasAlias('entity1_alias1', 'entity1_plural_alias')
        );
        self::assertTrue(
            $this->duplicateResolver->hasAlias('entity1_alias', 'entity1_plural_alias1')
        );
        self::assertFalse(
            $this->duplicateResolver->hasAlias('entity2_alias', 'entity2_plural_alias')
        );
    }

    public function testHasAliasForEnumEntity(): void
    {
        $this->expectInitializeAliases();

        self::assertTrue(
            $this->duplicateResolver->hasAlias('enum1_alias', 'enum1_plural_alias')
        );
        self::assertTrue(
            $this->duplicateResolver->hasAlias('enum1_alias1', 'enum1_plural_alias')
        );
        self::assertTrue(
            $this->duplicateResolver->hasAlias('enum1_alias', 'enum1_plural_alias1')
        );
        self::assertFalse(
            $this->duplicateResolver->hasAlias('enum2_alias', 'enum2_plural_alias')
        );
    }

    public function testGetUniqueAlias(): void
    {
        $this->expectInitializeAliases();

        self::assertEquals(
            'entity1_alias1',
            $this->duplicateResolver->getUniqueAlias('entity1_alias', 'entity1_plural_alias')
        );
        self::assertEquals(
            'entity1_alias11',
            $this->duplicateResolver->getUniqueAlias('entity1_alias1', 'entity1_plural_alias')
        );
        self::assertEquals(
            'entity1_alias1',
            $this->duplicateResolver->getUniqueAlias('entity1_alias', 'entity1_plural_alias1')
        );
        self::assertEquals(
            'entity2_alias',
            $this->duplicateResolver->getUniqueAlias('entity2_alias', 'entity2_plural_alias')
        );
    }

    public function testGetUniqueAliasForEnumEntity(): void
    {
        $this->expectInitializeAliases();

        self::assertEquals(
            'enum1_alias1',
            $this->duplicateResolver->getUniqueAlias('enum1_alias', 'enum1_plural_alias')
        );
        self::assertEquals(
            'enum1_alias11',
            $this->duplicateResolver->getUniqueAlias('enum1_alias1', 'enum1_plural_alias')
        );
        self::assertEquals(
            'enum1_alias1',
            $this->duplicateResolver->getUniqueAlias('enum1_alias', 'enum1_plural_alias1')
        );
        self::assertEquals(
            'enum2_alias',
            $this->duplicateResolver->getUniqueAlias('enum2_alias', 'enum2_plural_alias')
        );
    }

    public function testSaveAliasForNotConfigurableAndNotEnumEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The entity "Test\Entity3" must be configurable or must represent an enum option.'
        );

        $this->expectInitializeAliases();

        $this->duplicateResolver->saveAlias(
            'Test\Entity3',
            new EntityAlias('entity3_alias', 'entity3_plural_alias')
        );
    }

    public function testSaveAlias(): void
    {
        $entityClass = 'Test\Entity2';
        $alias = 'entity2_alias';
        $pluralAlias = 'entity2_plural_alias';

        $this->expectInitializeAliases();

        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('entity', $entityClass)
            ->willReturn(new Config(
                new EntityConfigId('entity', $entityClass),
                []
            ));
        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(new Config(
                new EntityConfigId('entity', $entityClass),
                ['entity_alias' => $alias, 'entity_plural_alias' => $pluralAlias]
            ));
        $this->configManager->expects(self::once())
            ->method('flush');

        $this->duplicateResolver->saveAlias($entityClass, new EntityAlias($alias, $pluralAlias));

        self::assertTrue(
            $this->duplicateResolver->hasAlias($alias, $pluralAlias)
        );
        self::assertEquals(
            new EntityAlias($alias, $pluralAlias),
            $this->duplicateResolver->getAlias($entityClass)
        );
    }

    public function testSaveAliasForEnumEntity(): void
    {
        $entityClass = 'Extend\Entity\EV_Enum_2';
        $alias = 'enum2_alias';
        $pluralAlias = 'enum2_plural_alias';

        $this->expectInitializeAliases();

        $this->configManager->expects(self::once())
            ->method('persist')
            ->with(new Config(
                new FieldConfigId('enum', 'Test\Entity2', 'enumField2', 'enum'),
                ['enum_code' => 'enum_2', 'entity_alias' => $alias, 'entity_plural_alias' => $pluralAlias]
            ));
        $this->configManager->expects(self::once())
            ->method('flush');

        $this->duplicateResolver->saveAlias($entityClass, new EntityAlias($alias, $pluralAlias));

        self::assertTrue(
            $this->duplicateResolver->hasAlias($alias, $pluralAlias)
        );
        self::assertEquals(
            new EntityAlias($alias, $pluralAlias),
            $this->duplicateResolver->getAlias($entityClass)
        );
    }

    public function testSaveAliasWhenAliasAlreadyExistInEntityConfig(): void
    {
        $entityClass = 'Test\Entity2';
        $alias = 'entity1_alias';
        $pluralAlias = 'entity1_plural_alias';

        $this->expectInitializeAliases();

        $this->configManager->expects(self::once())
            ->method('getEntityConfig')
            ->with('entity', $entityClass)
            ->willReturn(new Config(
                new EntityConfigId('entity', $entityClass),
                ['entity_alias' => $alias, 'entity_plural_alias' => $pluralAlias]
            ));
        $this->configManager->expects(self::never())
            ->method('persist');
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->duplicateResolver->saveAlias($entityClass, new EntityAlias($alias, $pluralAlias));

        self::assertTrue(
            $this->duplicateResolver->hasAlias($alias, $pluralAlias)
        );
        self::assertEquals(
            new EntityAlias($alias, $pluralAlias),
            $this->duplicateResolver->getAlias($entityClass)
        );
    }

    public function testSaveAliasWhenAliasAlreadyExistInEntityConfigForEnumEntity(): void
    {
        $entityClass = 'Extend\Entity\EV_Enum_1';
        $alias = 'enum1_alias';
        $pluralAlias = 'enum1_plural_alias';

        $this->expectInitializeAliases();

        $this->configManager->expects(self::never())
            ->method('persist');
        $this->configManager->expects(self::never())
            ->method('flush');

        $this->duplicateResolver->saveAlias($entityClass, new EntityAlias($alias, $pluralAlias));

        self::assertTrue(
            $this->duplicateResolver->hasAlias($alias, $pluralAlias)
        );
        self::assertEquals(
            new EntityAlias($alias, $pluralAlias),
            $this->duplicateResolver->getAlias($entityClass)
        );
    }
}
