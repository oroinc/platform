<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasLoader;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;
use Psr\Log\LoggerInterface;

class EntityAliasResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityAliasLoader */
    private $loader;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Cache */
    private $cache;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
    private $logger;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    protected function setUp()
    {
        $this->loader = $this->createMock(EntityAliasLoader::class);
        $this->cache = $this->createMock(Cache::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityAliasResolver = new EntityAliasResolver(
            $this->loader,
            $this->cache,
            $this->logger,
            true
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

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage An alias for "Test\UnknownEntity" entity not found.
     */
    public function testGetAliasForUnknownEntity()
    {
        $this->setLoadExpectations();

        $this->entityAliasResolver->getAlias('Test\UnknownEntity');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage A plural alias for "Test\UnknownEntity" entity not found.
     */
    public function testGetPluralAliasForUnknownEntity()
    {
        $this->setLoadExpectations();

        $this->entityAliasResolver->getPluralAlias('Test\UnknownEntity');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage The alias "unknown" is not associated with any entity class.
     */
    public function testGetClassByAliasForUnknownAlias()
    {
        $this->setLoadExpectations();

        $this->entityAliasResolver->getClassByAlias('unknown');
    }

    /**
     * @expectedException \Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException
     * @expectedExceptionMessage The plural alias "unknown" is not associated with any entity class.
     */
    public function testGetClassByPluralAliasForUnknownAlias()
    {
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
            ->willReturn($storage);

        $this->loader->expects(self::never())
            ->method('load');

        self::assertEquals(
            ['Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias')],
            $this->entityAliasResolver->getAll()
        );
    }
}
