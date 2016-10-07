<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

class EventTriggerCacheTest extends \PHPUnit_Framework_TestCase
{
    const TRIGGER_CLASS_NAME = 'stdClass';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->cache = new EventTriggerCache($this->registry);
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);
    }

    public function testSetProvider()
    {
        $provider = $this->prepareProvider([]);

        $this->assertAttributeEmpty('provider', $this->cache);
        $this->cache->setProvider($provider);
        $this->assertAttributeEquals($provider, 'provider', $this->cache);
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Event trigger cache provider is not defined
     */
    public function testBuildNoProvider()
    {
        $this->cache->build();
    }

    public function testHasTrigger()
    {
        $expectedProviderCalls = [
            // first call
            ['contains', [EventTriggerCache::BUILT], false],
            ['deleteAll'],
            ['save', [EventTriggerCache::DATA, $this->testTriggerData]],
            ['save', [EventTriggerCache::BUILT, true]],
            // second call
            ['contains', [EventTriggerCache::BUILT], true],
            ['fetch', [EventTriggerCache::BUILT], true],
            ['fetch', [EventTriggerCache::DATA], $this->testTriggerData],
        ];

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));

        $this->assertTrue($this->cache->hasTrigger('FirstEntity', ProcessTrigger::EVENT_CREATE));
        $this->assertFalse($this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Event trigger cache provider is not defined
     */
    public function testHasTriggerNoProvider()
    {
        $this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Event trigger class name is not defined
     */
    public function testNoTriggerClassNameException()
    {
        $this->cache->setProvider($this->prepareProvider([]));
        $this->cache->setTriggerClassName(null);

        $this->cache->build();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid repository
     */
    public function testInvalidTriggerRepository()
    {
        $this->cache->setProvider($this->prepareProvider([]));
        $this->cache->setTriggerClassName(self::TRIGGER_CLASS_NAME);

        $repository = $this->getMock(ObjectRepository::class);

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
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheProvider
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

    /**
     * @param array $data
     */
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
