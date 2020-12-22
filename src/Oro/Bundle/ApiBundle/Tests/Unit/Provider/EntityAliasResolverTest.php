<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ApiBundle\Provider\EntityAliasLoader;
use Oro\Bundle\ApiBundle\Provider\EntityAliasResolver;
use Oro\Bundle\ApiBundle\Provider\MutableEntityOverrideProvider;
use Oro\Bundle\EntityBundle\Exception\DuplicateEntityAliasException;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityAliasResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityAliasLoader|\PHPUnit\Framework\MockObject\MockObject */
    private $loader;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    protected function setUp(): void
    {
        $this->loader = $this->createMock(EntityAliasLoader::class);
        $this->cache = $this->createMock(Cache::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityAliasResolver = new EntityAliasResolver(
            $this->loader,
            new MutableEntityOverrideProvider(['Test\Entity2' => 'Test\Entity1']),
            $this->cache,
            $this->logger,
            ['api.yml']
        );
    }

    protected function setLoadExpectations()
    {
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('entity_aliases')
            ->willReturn(false);

        $this->loader->expects(self::once())
            ->method('load')
            ->willReturnCallback(
                function (EntityAliasStorage $storage) {
                    $storage->addEntityAlias(
                        'Test\Entity1',
                        new EntityAlias('entity1_alias', 'entity1_plural_alias')
                    );
                }
            );
    }

    public function testHasAliasForUnknownEntity()
    {
        $this->setLoadExpectations();

        self::assertFalse(
            $this->entityAliasResolver->hasAlias('Test\UnknownEntity')
        );
    }

    public function testGetAliasForUnknownEntity()
    {
        $this->expectException(\Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException::class);
        $this->expectExceptionMessage('An alias for "Test\UnknownEntity" entity not found.');

        $this->setLoadExpectations();

        $this->entityAliasResolver->getAlias('Test\UnknownEntity');
    }

    public function testGetPluralAliasForUnknownEntity()
    {
        $this->expectException(\Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException::class);
        $this->expectExceptionMessage('A plural alias for "Test\UnknownEntity" entity not found.');

        $this->setLoadExpectations();

        $this->entityAliasResolver->getPluralAlias('Test\UnknownEntity');
    }

    public function testGetClassByAliasForUnknownAlias()
    {
        $this->expectException(\Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException::class);
        $this->expectExceptionMessage('The alias "unknown" is not associated with any entity class.');

        $this->setLoadExpectations();

        $this->entityAliasResolver->getClassByAlias('unknown');
    }

    public function testGetClassByPluralAliasForUnknownAlias()
    {
        $this->expectException(\Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException::class);
        $this->expectExceptionMessage('The plural alias "unknown" is not associated with any entity class.');

        $this->setLoadExpectations();

        $this->entityAliasResolver->getClassByPluralAlias('unknown');
    }

    public function testHasAlias()
    {
        $this->setLoadExpectations();

        self::assertTrue(
            $this->entityAliasResolver->hasAlias('Test\Entity1')
        );
    }

    public function testGetAlias()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            'entity1_alias',
            $this->entityAliasResolver->getAlias('Test\Entity1')
        );
    }

    public function testGetPluralAlias()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            'entity1_plural_alias',
            $this->entityAliasResolver->getPluralAlias('Test\Entity1')
        );
    }

    public function testGetClassByAlias()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByAlias('entity1_alias')
        );
    }

    public function testGetClassByPluralAlias()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByPluralAlias('entity1_plural_alias')
        );
    }

    public function testGetAll()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            ['Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias')],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testWarmUpCache()
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with('entity_aliases');

        $this->setLoadExpectations();

        $this->entityAliasResolver->warmUpCache();
    }

    public function testClearCache()
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with('entity_aliases');

        $this->entityAliasResolver->clearCache();
    }

    public function testLoadFromCache()
    {
        $storage = new EntityAliasStorage();
        $storage->addEntityAlias('Test\Entity1', new EntityAlias('entity1_alias', 'entity1_plural_alias'));

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('entity_aliases')
            ->willReturn([null, $storage]);

        $this->loader->expects(self::never())
            ->method('load');

        self::assertEquals(
            ['Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias')],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testHasAliasForOverriddenEntity()
    {
        $this->setLoadExpectations();

        self::assertTrue(
            $this->entityAliasResolver->hasAlias('Test\Entity2')
        );
    }

    public function testGetAliasForOverriddenEntity()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            'entity1_alias',
            $this->entityAliasResolver->getAlias('Test\Entity2')
        );
    }

    public function testGetPluralAliasForOverriddenEntity()
    {
        $this->setLoadExpectations();

        self::assertEquals(
            'entity1_plural_alias',
            $this->entityAliasResolver->getPluralAlias('Test\Entity2')
        );
    }

    public function testShouldCreateCorrectStorage()
    {
        $this->expectException(DuplicateEntityAliasException::class);
        $this->expectExceptionMessage(
            'The alias "alias" cannot be used for the entity "Test\Entity2" because it is already '
            . 'used for the entity "Test\Entity1". To solve this problem you can '
            . 'use "entity_aliases" section in "Resources/config/oro/api.yml", '
            . 'use "entity_aliases" or "entity_alias_exclusions" section in "Resources/config/oro/entity.yml" or '
            . 'create a service to provide aliases for conflicting classes and register it '
            . 'with "oro_entity.alias_provider" tag in DI container.'
        );

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('entity_aliases')
            ->willReturn(false);
        $this->loader->expects(self::once())
            ->method('load')
            ->willReturnCallback(
                function (EntityAliasStorage $storage) {
                    $storage->addEntityAlias('Test\Entity1', new EntityAlias('alias', 'plural_alias'));
                    $storage->addEntityAlias('Test\Entity2', new EntityAlias('alias', 'plural_alias'));
                }
            );

        $this->entityAliasResolver->getAll();
    }
}
