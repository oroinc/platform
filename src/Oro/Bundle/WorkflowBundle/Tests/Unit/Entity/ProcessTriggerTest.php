<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
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
        $this->assertEquals($testValue, $this->entity->$getter());
    }

    /**
     * @return array
     */
    public function setGetDataProvider()
    {
        return [
            'event' => ['event', 'update'],
            'field' => ['field', 'status'],
            'queued' => ['queued', true, false],
            'timeShift' => ['timeShift', time()],
            'definition' => ['definition', new ProcessDefinition()],
            'cron' => ['cron', '* * * * *'],
            'createdAt' => ['createdAt', new \DateTime()],
            'updatedAt' => ['updatedAt', new \DateTime()],
        ];
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
        return [
            [
                'interval' => new \DateInterval('PT3600S'),
                'seconds' => 3600,
            ],
            [
                'interval' => new \DateInterval('P1DT2H3M4S'),
                'seconds' => 93784,
            ],
        ];
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
            ->setDefinition($importedDefinition)
            ->setCron('*/1 * * * *');

        $this->assertProcessTriggerEntitiesEquals($importedEntity, $this->entity, false);
        $this->entity->import($importedEntity);
        $this->assertProcessTriggerEntitiesEquals($importedEntity, $this->entity);
    }

    /**
     * @param ProcessTrigger $expectedEntity
     * @param ProcessTrigger $actualEntity
     * @param bool $isEquals
     */
    protected function assertProcessTriggerEntitiesEquals($expectedEntity, $actualEntity, $isEquals = true)
    {
        $method = $isEquals ? 'assertEquals' : 'assertNotEquals';
        $this->$method($expectedEntity->getEvent(), $actualEntity->getEvent());
        $this->$method($expectedEntity->getField(), $actualEntity->getField());
        $this->$method($expectedEntity->getPriority(), $actualEntity->getPriority());
        $this->$method($expectedEntity->isQueued(), $actualEntity->isQueued());
        $this->$method($expectedEntity->getTimeShift(), $actualEntity->getTimeShift());
        $this->$method($expectedEntity->getDefinition(), $actualEntity->getDefinition());
    }

    /**
     * @param array $trigger1Attributes
     * @param array $trigger2Attributes
     * @param true $expected
     *
     * @dataProvider testIsDefinitiveEqualData
     */
    public function testIsDefinitiveEqual(array $trigger1Attributes, array $trigger2Attributes, $expected)
    {
        $trigger1 = $this->createProcessTriggerByAttributes($trigger1Attributes);
        $trigger2 = $this->createProcessTriggerByAttributes($trigger2Attributes);

        $result = $trigger1->isDefinitiveEqual($trigger2);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function testIsDefinitiveEqualData()
    {
        return [
            'equal full' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => 'd1'],
                'trigger2' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => 'd1'],
                'expected' => true
            ],
            'equal partial: no definition name' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => null],
                'trigger2' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => null],
                'expected' => true
            ],
            'equal partial: no definition' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *'],
                'trigger2' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *'],
                'expected' => true
            ],
            'equal partial: no cron and definition' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1'],
                'trigger2' => ['event' => 'event1', 'field' => 'field1'],
                'expected' => true
            ],
            'equal partial: no field, cron, definition' => [
                'trigger1' => ['event' => 'event1'],
                'trigger2' => ['event' => 'event1'],
                'expected' => true
            ],
            'equal: empty' => [
                'trigger1' => [],
                'trigger2' => [],
                'expected' => true
            ],
            'not equal: definition name' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => 'd1'],
                'trigger2' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => 'd2'],
                'expected' => false
            ],
            'not equal: definition name strict' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => '0'],
                'trigger2' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *', 'definition' => null],
                'expected' => false
            ],
            'not equal: cron' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '1 * * * *'],
                'trigger2' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *'],
                'expected' => false
            ],
            'not equal: field' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *'],
                'trigger2' => ['event' => 'event1', 'field' => 'field2', 'cron' => '* * * * *'],
                'expected' => false
            ],
            'not equal: event' => [
                'trigger1' => ['event' => 'event1', 'field' => 'field1', 'cron' => '* * * * *'],
                'trigger2' => ['event' => 'event2', 'field' => 'field1', 'cron' => '* * * * *'],
                'expected' => false
            ]
        ];
    }

    /**
     * @param array $attributes
     * @return ProcessTrigger
     */
    public function createProcessTriggerByAttributes(array $attributes)
    {
        $trigger = new ProcessTrigger();

        if (isset($attributes['event'])) {
            $trigger->setEvent($attributes['event']);
        }

        if (isset($attributes['field'])) {
            $trigger->setField($attributes['field']);
        }

        if (isset($attributes['cron'])) {
            $trigger->setCron($attributes['cron']);
        }

        if (isset($attributes['definition'])) {
            $definition = new ProcessDefinition();
            $definition->setName($attributes['definition']);

            $trigger->setDefinition($definition);
        }

        return $trigger;
    }
}
