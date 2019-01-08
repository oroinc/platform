<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\DuplicateEntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class DuplicateEntityAliasResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var DuplicateEntityAliasResolver */
    private $duplicateResolver;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->duplicateResolver = new DuplicateEntityAliasResolver(
            $this->configManager
        );
    }

    private function expectInitializeAliases()
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
    }

    public function testGetAlias()
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

    public function testHasAlias()
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

    public function testGetUniqueAlias()
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The entity "Test\Entity3" must be configurable.
     */
    public function testSaveAliasForNotConfigurableEntity()
    {
        $this->expectInitializeAliases();

        $this->duplicateResolver->saveAlias(
            'Test\Entity3',
            new EntityAlias('entity3_alias', 'entity3_plural_alias')
        );
    }

    public function testSaveAlias()
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
}
