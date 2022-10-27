<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Context;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;

class ContextRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new ContextRegistry();
    }

    public function testGetByStepExecution()
    {
        $fooStepExecution = $this->createStepExecution();
        $fooContext = $this->registry->getByStepExecution($fooStepExecution);
        static::assertInstanceOf(StepExecutionProxyContext::class, $fooContext);
        static::assertSame($fooContext, $this->registry->getByStepExecution($fooStepExecution));

        $barStepExecution = $this->createStepExecution('job2');
        $barContext = $this->registry->getByStepExecution($barStepExecution);
        static::assertNotSame($barContext, $fooContext);

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->method('getAlias')->willReturn('job2');
        $this->registry->clear($jobInstance);
        $barContext2 = $this->registry->getByStepExecution($barStepExecution);
        static::assertNotSame($barContext, $barContext2);
    }

    private function createStepExecution(string $alias = null): StepExecution
    {
        $stepExecution = $this->createMock(StepExecution::class);

        if ($alias) {
            $jobExecution = $this->createMock(JobExecution::class);
            $jobInstance = $this->createMock(JobInstance::class);
            $jobExecution->method('getJobInstance')->willReturn($jobInstance);
            $stepExecution->method('getJobExecution')->willReturn($jobExecution);
            $jobInstance->method('getAlias')->willReturn($alias);
        }

        return $stepExecution;
    }
}
