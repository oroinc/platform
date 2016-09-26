<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\WorkflowBundle\Cache\EventTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\EventTriggerRepositoryInterface;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository;

class EventTriggerCacheTest extends \PHPUnit_Framework_TestCase
{
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
        $this->cache = new EventTriggerCache();
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

        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));
        $this->cache->setEventTriggerRepository($this->prepareRepository($this->testTriggerData));
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

        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));
        $this->cache->setEventTriggerRepository($this->prepareRepository($this->testTriggerData));

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
     * @expectedExceptionMessage Event trigger repository is not defined
     */
    public function testNoRepositoryException()
    {
        $this->cache->setProvider($this->prepareProvider([]));

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
     * @return \PHPUnit_Framework_MockObject_MockObject|EventTriggerRepositoryInterface
     */
    protected function prepareRepository(array $data)
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

        return $repository;
    }
}
