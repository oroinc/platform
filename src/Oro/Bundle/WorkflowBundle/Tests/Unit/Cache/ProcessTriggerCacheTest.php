<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Cache;

use Oro\Bundle\WorkflowBundle\Cache\ProcessTriggerCache;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ProcessTriggerCache
     */
    protected $cache;

    /**
     * @var array
     */
    protected $testTriggerData = array(
        'FirstEntity'  => array(ProcessTrigger::EVENT_CREATE, ProcessTrigger::EVENT_UPDATE),
        'SecondEntity' => array(ProcessTrigger::EVENT_DELETE),
    );

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->cache = new ProcessTriggerCache($this->registry);
    }

    public function testSetProvider()
    {
        $provider = $this->prepareProvider(array());

        $this->assertAttributeEmpty('provider', $this->cache);
        $this->cache->setProvider($provider);
        $this->assertAttributeEquals($provider, 'provider', $this->cache);
    }

    public function testBuild()
    {
        $expectedProviderCalls = array(
            array('deleteAll'),
            array('save', array(ProcessTriggerCache::DATA, $this->testTriggerData)),
            array('save', array(ProcessTriggerCache::BUILT, true)),
        );

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));
        $this->assertEquals($this->testTriggerData, $this->cache->build());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Process trigger cache provider is not defined
     */
    public function testBuildNoProvider()
    {
        $this->cache->build();
    }

    public function testHasTrigger()
    {
        $expectedProviderCalls = array(
            // first call
            array('contains', array(ProcessTriggerCache::BUILT), false),
            array('deleteAll'),
            array('save', array(ProcessTriggerCache::DATA, $this->testTriggerData)),
            array('save', array(ProcessTriggerCache::BUILT, true)),
            // second call
            array('contains', array(ProcessTriggerCache::BUILT), true),
            array('fetch', array(ProcessTriggerCache::BUILT), true),
            array('fetch', array(ProcessTriggerCache::DATA), $this->testTriggerData),
        );

        $this->prepareRegistryForBuild($this->testTriggerData);
        $this->cache->setProvider($this->prepareProvider($expectedProviderCalls));

        $this->assertTrue($this->cache->hasTrigger('FirstEntity', ProcessTrigger::EVENT_CREATE));
        $this->assertFalse($this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Process trigger cache provider is not defined
     */
    public function testHasTriggerNoProvider()
    {
        $this->cache->hasTrigger('UnknownEntity', ProcessTrigger::EVENT_DELETE);
    }

    /**
     * @param array $calls
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareProvider(array $calls)
    {
        $cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(array('deleteAll', 'save', 'contains', 'fetch'))
            ->getMockForAbstractClass();

        foreach ($calls as $iteration => $call) {
            $method = $call[0];
            $with   = !empty($call[1]) ? $call[1] : null;
            $return = !empty($call[2]) ? $call[2] : null;

            $mocker = $cacheProvider->expects($this->at($iteration))->method($method);

            if ($with) {
                call_user_func_array(array($mocker, 'with'), $with);
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
        $triggers = array();
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
        $triggerClass = 'OroWorkflowBundle:ProcessTrigger';

        $repository = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessTriggerRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())->method('findAllWithDefinitions')
            ->will($this->returnValue($triggers));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())->method('getRepository')->with($triggerClass)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->any())->method('getManagerForClass')->with($triggerClass)
            ->will($this->returnValue($entityManager));
    }
}
