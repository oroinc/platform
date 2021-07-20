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

class EventTriggerCacheTest extends \PHPUnit\Framework\TestCase
{
    const TRIGGER_CLASS_NAME = 'stdClass';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var EventTriggerCache
     */
    protected $cache;

    /**
     * @var array
     */
    protected $testTriggerData = [
        'FirstEntity'  => [ProcessTrigger::EVENT_CREATE, ProcessTrigger::EVENT_UPDATE],
        'SecondEntity' => [ProcessTrigger::EVENT_DELETE],
    ];

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->cache = new class($this->registry) extends EventTriggerCache {
            public function xgetProvider(): ?CacheProvider
            {
                return $this->provider;
            }
        };
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);
    }

    public function testSetProvider()
    {
        $provider = $this->prepareProvider([]);

        static::assertEmpty($this->cache->xgetProvider());
        $this->cache->setProvider($provider);
        static::assertSame($provider, $this->cache->xgetProvider());
    }

    public function testBuild()
    {
        $expectedProviderCalls = [
            ['deleteAll'],
            ['save', [EventTriggerCache::DATA, $this->testTriggerData]],
            ['save', [EventTriggerCache::BUILT, true]],
        ];

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));
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
        $expectedProviderCalls = [
            // first call
            ['fetch', [EventTriggerCache::BUILT], false],
            ['deleteAll'],
            ['save', [EventTriggerCache::DATA, $this->testTriggerData]],
            ['save', [EventTriggerCache::BUILT, true]],
            // second call
            ['fetch', [EventTriggerCache::BUILT], true],
            ['fetch', [EventTriggerCache::DATA], $this->testTriggerData],
        ];

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));

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

        $this->cache->setProvider($this->prepareProvider([]));
        $this->cache->setTriggerClassName(null);

        $this->cache->build();
    }

    public function testInvalidTriggerRepository()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid repository');

        $this->cache->setProvider($this->prepareProvider([]));
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);

        $repository = $this->createMock(ObjectRepository::class);

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($entityManager);

        $this->cache->build();
    }

    /**
     * @param array $calls
     * @return \PHPUnit\Framework\MockObject\MockObject|CacheProvider
     */
    protected function prepareProvider(array $calls)
    {
        $cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll', 'save', 'contains', 'fetch'])
            ->getMockForAbstractClass();

        foreach ($calls as $iteration => $call) {
            $method = $call[0];
            $with   = !empty($call[1]) ? $call[1] : null;
            $return = !empty($call[2]) ? $call[2] : null;

            $mocker = $cacheProvider->expects($this->at($iteration))->method($method);

            if ($with) {
                call_user_func_array([$mocker, 'with'], $with);
            }

            if ($return) {
                $mocker->will($this->returnValue($return));
            }
        }

        return $cacheProvider;
    }

    protected function prepareRegistryForBuild(array $data)
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
        $repository = $this->getMockBuilder(ProcessTriggerRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects($this->any())->method('getAvailableEventTriggers')->will($this->returnValue($triggers));

        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::TRIGGER_CLASS_NAME)
            ->willReturn($entityManager);
    }
}
