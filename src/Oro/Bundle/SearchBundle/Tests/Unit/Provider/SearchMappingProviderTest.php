<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchMappingProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    protected $testMapping = [
        'Oro\TestBundle\Entity\TestEntity' => [
            'alias'  => 'test_entity',
            'fields' => [
                [
                    'name' => 'firstname',
                    'target_type' => 'text',
                    'target_columns' => ['firstname']
                ],
                [
                    'name' => 'qty',
                    'target_type' => 'integer',
                    'target_columns' => ['qty']
                ]
            ]
        ]
    ];

    /**
     * @var SearchMappingProvider
     */
    protected $provider;

    /**
     * @var Cache|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $cache;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cache = $this->createMock(Cache::class);
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->cache, $this->eventDispatcher);
    }

    public function testGetEntitiesListAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntitiesListAliases()
        );
    }

    public function testGetEntityAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases(['Oro\TestBundle\Entity\TestEntity'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.
     */
    public function testGetEntityAliasesForUnknownEntity()
    {
        $this->getProvider()->getEntityAliases(
            ['Oro\TestBundle\Entity\TestEntity', 'Oro\TestBundle\Entity\UnknownEntity']
        );
    }

    public function testGetEntityAliasesForEmptyClassNames()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases()
        );
    }

    public function testGetEntityAlias()
    {
        $this->assertEquals(
            'test_entity',
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\TestEntity')
        );
    }

    public function testGetEntityAliasForUnknownEntity()
    {
        $this->assertNull(
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\UnknownEntity')
        );
    }

    public function testGetEntityClasses()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity'],
            $this->getProvider()->getEntityClasses()
        );
    }

    public function testIsClassSupported()
    {
        $provider = $this->getProvider();

        $this->assertTrue($provider->isClassSupported('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($provider->isClassSupported('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testHasFieldsMapping()
    {
        $provider = $this->getProvider();

        $this->assertTrue($provider->hasFieldsMapping('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($provider->hasFieldsMapping('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testGetEntityMapParameter()
    {
        $provider = $this->getProvider();

        $this->assertEquals(
            'test_entity',
            $provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'alias')
        );
        $this->assertFalse(
            $provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'badParameter', false)
        );
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Oro\TestBundle\Entity\TestEntity',
            $this->getProvider()->getEntityClass('test_entity')
        );
    }

    public function testGetEntityClassForUnknownAlias()
    {
        $this->assertNull(
            $this->getProvider()->getEntityClass('unknown_entity')
        );
    }

    public function testGetMappingConfigCached()
    {
        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->assertEquals($this->testMapping, $this->getProvider()->getMappingConfig());
    }

    public function testGetMappingConfig()
    {
        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(SearchMappingProvider::CACHE_KEY)
            ->willReturn(false);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($eventName, SearchMappingCollectEvent $event) {
                $this->assertEquals(SearchMappingCollectEvent::EVENT_NAME, $eventName);
                $this->assertEquals($this->testMapping, $event->getMappingConfig());

                $event->setMappingConfig([]);
            });

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(SearchMappingProvider::CACHE_KEY, []);

        $this->assertEquals([], $this->getProvider(false)->getMappingConfig());
        $this->assertEquals([], $this->getProvider(false)->getMappingConfig());
    }

    public function testClearMappingCache()
    {
        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with(SearchMappingProvider::CACHE_KEY);

        $this->getProvider(false)->clearCache();
    }

    /**
     * @param bool $mockFetch
     *
     * @return SearchMappingProvider
     */
    protected function getProvider($mockFetch = true)
    {
        if (!$this->provider) {
            $this->provider = new SearchMappingProvider($this->eventDispatcher, $this->cache);
            $this->provider->setMappingConfig($this->testMapping);

            if ($mockFetch) {
                $this->cache
                    ->expects($this->once())
                    ->method('fetch')
                    ->with(SearchMappingProvider::CACHE_KEY)
                    ->willReturn($this->testMapping);
            }
        }

        return $this->provider;
    }
}
