<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

class AfterJobExecutionEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $jobExecution = new JobExecution();
        $jobResult = new JobResult();

        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);
        $this->assertSame($jobExecution, $event->getJobExecution());
        $this->assertSame($jobResult, $event->getJobResult());
    }
}
