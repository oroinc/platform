<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityAliasStorage;

class EntityAliasResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $loader;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    protected function setUp()
    {
        $this->loader = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityAliasLoader')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMock('Doctrine\Common\Cache\Cache');

        $this->entityAliasResolver = new EntityAliasResolver($this->loader, $this->cache, true);
    }

    protected function setLoadExpectations()
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(EntityAliasResolver::CACHE_KEY)
            ->willReturn(false);

        $this->loader->expects($this->once())
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

        $this->assertFalse(
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

        $this->assertTrue(
            $this->entityAliasResolver->hasAlias('Test\Entity1')
        );
    }

    public function testGetAlias()
    {
        $this->setLoadExpectations();

        $this->assertEquals(
            'entity1_alias',
            $this->entityAliasResolver->getAlias('Test\Entity1')
        );
    }

    public function testGetPluralAlias()
    {
        $this->setLoadExpectations();

        $this->assertEquals(
            'entity1_plural_alias',
            $this->entityAliasResolver->getPluralAlias('Test\Entity1')
        );
    }

    public function testGetClassByAlias()
    {
        $this->setLoadExpectations();

        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByAlias('entity1_alias')
        );
    }

    public function testGetClassByPluralAlias()
    {
        $this->setLoadExpectations();

        $this->assertEquals(
            'Test\Entity1',
            $this->entityAliasResolver->getClassByPluralAlias('entity1_plural_alias')
        );
    }

    public function testGetAll()
    {
        $this->setLoadExpectations();

        $this->assertEquals(
            ['Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias')],
            $this->entityAliasResolver->getAll()
        );
    }

    public function testWarmUpCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(EntityAliasResolver::CACHE_KEY);

        $this->setLoadExpectations();

        $this->entityAliasResolver->warmUpCache();
    }

    public function testClearCache()
    {
        $this->cache->expects($this->once())
            ->method('delete')
            ->with(EntityAliasResolver::CACHE_KEY);

        $this->entityAliasResolver->clearCache();
    }

    public function testLoadFromCache()
    {
        $storage = new EntityAliasStorage();
        $storage->addEntityAlias('Test\Entity1', new EntityAlias('entity1_alias', 'entity1_plural_alias'));

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(EntityAliasResolver::CACHE_KEY)
            ->willReturn($storage);

        $this->loader->expects($this->never())
            ->method('load');

        $this->assertEquals(
            ['Test\Entity1' => new EntityAlias('entity1_alias', 'entity1_plural_alias')],
            $this->entityAliasResolver->getAll()
        );
    }
}
