<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Oro\Bundle\WorkflowBundle\Model\TransitionTriggerCronScheduler;
use Oro\Component\Testing\Unit\EntityTrait;

class TransitionTriggerCronSchedulerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var TransitionTriggerCronScheduler
     */
    private $scheduler;

    /**
     * @var DeferredScheduler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deferredScheduler;

    protected function setUp()
    {
        $this->deferredScheduler = $this->getMockBuilder(DeferredScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scheduler = new TransitionTriggerCronScheduler($this->deferredScheduler);
    }

    public function testAddSchedule()
    {
        $cronTrigger = $this->createTrigger(['cron' => '* * * * *', 'id' => 42]);

        $this->deferredScheduler->expects($this->once())
            ->method('addSchedule')
            ->with(
                'oro:workflow:handle-transition-cron-trigger',
                $this->callback(function ($argumentsCallback) {
                    if (!is_callable($argumentsCallback)) {
                        return false;
                    }

                    return ['--id=42'] === call_user_func($argumentsCallback);
                }),
                '* * * * *'
            );

        $this->scheduler->addSchedule($cronTrigger);
    }

    private function createTrigger(array $properties)
    {
        $trigger = new TransitionCronTrigger();
        foreach ($properties as $propertyName => $propertyValue) {
            $this->setValue($trigger, $propertyName, $propertyValue);
        }

        return $trigger;
    }

    public function testRemoveSchedule()
    {
        $cronTrigger = $this->createTrigger(['cron' => '* * * * *', 'id' => 42]);

        $this->deferredScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with(
                'oro:workflow:handle-transition-cron-trigger',
                ['--id=42'],
                '* * * * *'
            );
        $this->scheduler->removeSchedule($cronTrigger);
    }

    public function testFlush()
    {
        $this->deferredScheduler->expects($this->once())->method('flush');
        $this->scheduler->flush();
    }
}
