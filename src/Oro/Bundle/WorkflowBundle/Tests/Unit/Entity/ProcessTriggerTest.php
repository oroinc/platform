<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

class ProcessTriggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessTrigger
     */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new ProcessTrigger();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());

        $testValue = 1;
        $reflectionProperty = new \ReflectionProperty('\Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger', 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->entity, $testValue);

        $this->assertEquals($testValue, $this->entity->getId());
    }

    /**
     * @param mixed $propertyName
     * @param mixed $testValue
     * @param mixed $defaultValue
     * @dataProvider setGetDataProvider
     */
    public function testSetGetEntity($propertyName, $testValue, $defaultValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = (is_bool($testValue) ? 'is' : 'get') . ucfirst($propertyName);

        $this->assertSame($defaultValue, $this->entity->$getter());
        $this->assertSame($this->entity, $this->entity->$setter($testValue));
        $this->assertSame($testValue, $this->entity->$getter());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return array(
            'event' => array('event', 'update'),
            'field' => array('field', 'status'),
            'queued' => array('queued', true, false),
            'timeShift' => array('timeShift', time()),
            'definition' => array('definition', new ProcessDefinition()),
            'createdAt' => array('createdAt', new \DateTime()),
            'updatedAt' => array('updatedAt', new \DateTime()),
        );
    }

    /**
     * @param \DateInterval $interval
     * @param $seconds
     * @dataProvider dateIntervalAndSecondsDataProvider
     */
    public function testConvertDateIntervalToSeconds(\DateInterval $interval, $seconds)
    {
        $this->assertEquals($seconds, ProcessTrigger::convertDateIntervalToSeconds($interval));
    }

    /**
     * @param \DateInterval $interval
     * @param $seconds
     * @dataProvider dateIntervalAndSecondsDataProvider
     */
    public function testConvertSecondsToDateInterval(\DateInterval $interval, $seconds)
    {
        $actualInterval = ProcessTrigger::convertSecondsToDateInterval($seconds);

        $this->assertEquals(
            ProcessTrigger::convertDateIntervalToSeconds($interval),
            ProcessTrigger::convertDateIntervalToSeconds($actualInterval)
        );
    }

    /**
     * @return array
     */
    public function dateIntervalAndSecondsDataProvider()
    {
        return array(
            array(
                'interval' => new \DateInterval('PT3600S'),
                'seconds' => 3600,
            ),
            array(
                'interval' => new \DateInterval('P1DT2H3M4S'),
                'seconds' => 93784,
            ),
        );
    }

    public function testSetGetTimeShiftInterval()
    {
        $this->assertNull($this->entity->getTimeShift());
        $this->assertNull($this->entity->getTimeShiftInterval());

        $this->entity->setTimeShiftInterval(new \DateInterval('PT1H'));
        $this->assertEquals(3600, $this->entity->getTimeShift());
        $this->assertEquals(3600, ProcessTrigger::convertDateIntervalToSeconds($this->entity->getTimeShiftInterval()));

        $this->entity->setTimeShiftInterval(null);
        $this->assertNull($this->entity->getTimeShift());
        $this->assertNull($this->entity->getTimeShiftInterval());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->prePersist();

        $this->assertInstanceOf('\DateTime', $this->entity->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $this->entity->getUpdatedAt());
        $this->assertEquals('UTC', $this->entity->getCreatedAt()->getTimezone()->getName());
        $this->assertEquals('UTC', $this->entity->getUpdatedAt()->getTimezone()->getName());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->preUpdate();

        $this->assertInstanceOf('\DateTime', $this->entity->getUpdatedAt());
        $this->assertEquals('UTC', $this->entity->getUpdatedAt()->getTimezone()->getName());
    }

    public function testImport()
    {
        $importedDefinition = new ProcessDefinition();

        $importedEntity = new ProcessTrigger();
        $importedEntity
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField('testField')
            ->setPriority(Job::PRIORITY_HIGH)
            ->setQueued(true)
            ->setTimeShift(123)
            ->setDefinition($importedDefinition);

        $this->assertProcessTriggerEntitiesEquals($importedEntity, $this->entity, false);
        $this->entity->import($importedEntity);
        $this->assertProcessTriggerEntitiesEquals($importedEntity, $this->entity);
    }

    protected function assertProcessTriggerEntitiesEquals($expected_entity, $actual_entity, $isEquals = true)
    {
        $method = $isEquals ? 'assertEquals' : 'assertNotEquals';
        $this->$method($expected_entity->getEvent(), $actual_entity->getEvent());
        $this->$method($expected_entity->getField(), $actual_entity->getField());
        $this->$method($expected_entity->getPriority(), $actual_entity->getPriority());
        $this->$method($expected_entity->isQueued(), $actual_entity->isQueued());
        $this->$method($expected_entity->getTimeShift(), $actual_entity->getTimeShift());
        $this->$method($expected_entity->getDefinition(), $actual_entity->getDefinition());
    }
}
