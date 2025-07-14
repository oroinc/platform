<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Event;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use PHPUnit\Framework\TestCase;

class AfterJobExecutionEventTest extends TestCase
{
    public function testEvent(): void
    {
        $jobExecution = new JobExecution();
        $jobResult = new JobResult();

        $event = new AfterJobExecutionEvent($jobExecution, $jobResult);
        $this->assertSame($jobExecution, $event->getJobExecution());
        $this->assertSame($jobResult, $event->getJobResult());
    }
}
