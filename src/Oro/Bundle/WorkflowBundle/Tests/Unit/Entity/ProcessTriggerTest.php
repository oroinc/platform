<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessPriority;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProcessTriggerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessTrigger */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new ProcessTrigger();
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());

        $testValue = 1;
        ReflectionUtil::setId($this->entity, $testValue);
        $this->assertSame($testValue, $this->entity->getId());
    }

    /**
     * @dataProvider setGetDataProvider
     */
    public function testSetGetEntity(string $propertyName, mixed $testValue, mixed $defaultValue = null)
    {
        $setter = 'set' . ucfirst($propertyName);
        $getter = (is_bool($testValue) ? 'is' : 'get') . ucfirst($propertyName);

        $this->assertSame($defaultValue, $this->entity->$getter());
        $this->assertSame($this->entity, $this->entity->$setter($testValue));
        $this->assertEquals($testValue, $this->entity->$getter());
    }

    public function setGetDataProvider(): array
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
     * @dataProvider dateIntervalAndSecondsDataProvider
     */
    public function testConvertDateIntervalToSeconds(\DateInterval $interval, int $seconds)
    {
        $this->assertEquals($seconds, ProcessTrigger::convertDateIntervalToSeconds($interval));
    }

    /**
     * @dataProvider dateIntervalAndSecondsDataProvider
     */
    public function testConvertSecondsToDateInterval(\DateInterval $interval, int $seconds)
    {
        $actualInterval = ProcessTrigger::convertSecondsToDateInterval($seconds);

        $this->assertEquals(
            ProcessTrigger::convertDateIntervalToSeconds($interval),
            ProcessTrigger::convertDateIntervalToSeconds($actualInterval)
        );
    }

    public function dateIntervalAndSecondsDataProvider(): array
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

        $this->assertInstanceOf(\DateTime::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->entity->getUpdatedAt());
        $this->assertEquals('UTC', $this->entity->getCreatedAt()->getTimezone()->getName());
        $this->assertEquals('UTC', $this->entity->getUpdatedAt()->getTimezone()->getName());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->preUpdate();

        $this->assertInstanceOf(\DateTime::class, $this->entity->getUpdatedAt());
        $this->assertEquals('UTC', $this->entity->getUpdatedAt()->getTimezone()->getName());
    }

    public function testImport()
    {
        $importedDefinition = new ProcessDefinition();

        $importedEntity = new ProcessTrigger();
        $importedEntity
            ->setEvent(ProcessTrigger::EVENT_UPDATE)
            ->setField('testField')
            ->setPriority(ProcessPriority::PRIORITY_HIGH)
            ->setQueued(true)
            ->setTimeShift(123)
            ->setDefinition($importedDefinition)
            ->setCron('*/1 * * * *');

        $this->assertProcessTriggerEntitiesEquals($importedEntity, $this->entity, false);
        $this->entity->import($importedEntity);
        $this->assertProcessTriggerEntitiesEquals($importedEntity, $this->entity);
    }

    private function assertProcessTriggerEntitiesEquals(
        ProcessTrigger $expectedEntity,
        ProcessTrigger $actualEntity,
        bool $isEquals = true
    ): void {
        $method = $isEquals ? 'assertEquals' : 'assertNotEquals';
        $this->$method($expectedEntity->getEvent(), $actualEntity->getEvent());
        $this->$method($expectedEntity->getField(), $actualEntity->getField());
        $this->$method($expectedEntity->getPriority(), $actualEntity->getPriority());
        $this->$method($expectedEntity->isQueued(), $actualEntity->isQueued());
        $this->$method($expectedEntity->getTimeShift(), $actualEntity->getTimeShift());
        $this->$method($expectedEntity->getDefinition(), $actualEntity->getDefinition());
    }

    /**
     * @dataProvider testIsDefinitiveEqualData
     */
    public function testIsDefinitiveEqual(array $trigger1Attributes, array $trigger2Attributes, bool $expected)
    {
        $trigger1 = $this->createProcessTriggerByAttributes($trigger1Attributes);
        $trigger2 = $this->createProcessTriggerByAttributes($trigger2Attributes);

        $result = $trigger1->isDefinitiveEqual($trigger2);

        $this->assertEquals($expected, $result);
    }

    public function testIsDefinitiveEqualData(): array
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

    public function createProcessTriggerByAttributes(array $attributes): ProcessTrigger
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

    public function testGetEntityClass()
    {
        $this->assertNull($this->entity->getEntityClass());

        $definition = new ProcessDefinition();
        $definition->setRelatedEntity('test class name');

        $this->entity->setDefinition($definition);

        $this->assertEquals($definition->getRelatedEntity(), $this->entity->getEntityClass());
    }
}
