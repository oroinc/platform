<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;
use Oro\Component\Testing\ReflectionUtil;

class EventTriggerCacheTest extends \PHPUnit\Framework\TestCase
{
    private const TRIGGER_CLASS_NAME = 'stdClass';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EventTriggerCache */
    private $cache;

    /** @var array */
    private $testTriggerData = [
        'FirstEntity'  => [ProcessTrigger::EVENT_CREATE, ProcessTrigger::EVENT_UPDATE],
        'SecondEntity' => [ProcessTrigger::EVENT_DELETE],
    ];

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->cache = new EventTriggerCache($this->registry);
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);
    }

    public function testSetProvider()
    {
        self::assertEmpty(ReflectionUtil::getPropertyValue($this->cache, 'provider'));

        $cacheProvider = $this->createMock(CacheProvider::class);
        $this->cache->setProvider($cacheProvider);
        self::assertSame($cacheProvider, ReflectionUtil::getPropertyValue($this->cache, 'provider'));
    }

    public function testBuild()
    {
        $cacheProvider = $this->createMock(CacheProvider::class);
        $cacheProvider->expects(self::once())
            ->method('deleteAll');
        $cacheProvider->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                [EventTriggerCache::DATA, $this->testTriggerData],
                [EventTriggerCache::BUILT, true]
            );

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($cacheProvider);

        $this->assertEquals($this->testTriggerData, $this->cache->build());
    }

    public function testBuildNoProvider()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Event trigger cache provider is not defined');

        $this->cache->build();
    }

    public function testHasTrigger()
    {
        $cacheProvider = $this->createMock(CacheProvider::class);
        $cacheProvider->expects(self::once())
            ->method('deleteAll');
        $cacheProvider->expects(self::exactly(3))
            ->method('fetch')
            ->withConsecutive(
                [EventTriggerCache::BUILT],
                [EventTriggerCache::BUILT],
                [EventTriggerCache::DATA]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                $this->testTriggerData
            );
        $cacheProvider->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                [EventTriggerCache::DATA, $this->testTriggerData],
                [EventTriggerCache::BUILT, true]
            );

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($cacheProvider);

        $this->assertTrue($this->cache->hasTrigger('FirstEntity', ProcessTrigger::EVENT_CREATE));
        $this->assertFalse($this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE));
    }

    public function testHasTriggerBuiltWithoutData()
    {
        $cacheProvider = $this->createMock(CacheProvider::class);
        $cacheProvider->expects(self::once())
            ->method('deleteAll');
        $cacheProvider->expects(self::exactly(2))
            ->method('fetch')
            ->withConsecutive(
                [EventTriggerCache::BUILT],
                [EventTriggerCache::DATA]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $cacheProvider->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                [EventTriggerCache::DATA, $this->testTriggerData],
                [EventTriggerCache::BUILT, true]
            );

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($cacheProvider);

        $this->assertTrue($this->cache->hasTrigger('FirstEntity', ProcessTrigger::EVENT_CREATE));
    }

    public function testHasTriggerNoProvider()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Event trigger cache provider is not defined');

        $this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE);
    }

    public function testNoTriggerClassNameException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Event trigger class name is not defined');

        $this->cache->setProvider($this->createMock(CacheProvider::class));
        $this->cache->setTriggerClassName(null);

        $this->cache->build();
    }

    public function testInvalidTriggerRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid repository');

        $this->cache->setProvider($this->createMock(CacheProvider::class));
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);

        $repository = $this->createMock(ObjectRepository::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($entityManager);

        $this->cache->build();
    }

    private function prepareRegistryForBuild(array $data)
    {
        // generate triggers
        $triggers = [];
        foreach ($data as $entityClass => $events) {
            $definition = new ProcessDefinition();
            $definition->setRelatedEntity($entityClass);

            foreach ($events as $event) {
                $trigger = new ProcessTrigger();
                $trigger->setDefinition($definition)
                    ->setEvent($event);

                $triggers[] = $trigger;
            }
        }

        // set mocks
        $repository = $this->createMock(ProcessTriggerRepository::class);
        $repository->expects(self::any())
            ->method('getAvailableEventTriggers')
            ->willReturn($triggers);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getRepository')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($entityManager);
    }
}
