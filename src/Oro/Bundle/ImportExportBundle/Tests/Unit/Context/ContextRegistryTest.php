<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use PHPUnit\Framework\MockObject\MockObject;

class ContextRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextRegistry */
    protected $registry;

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

        /** @var MockObject|JobInstance $jobInstance */
        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->method('getAlias')->willReturn('job2');
        $this->registry->clear($jobInstance);
        $barContext2 = $this->registry->getByStepExecution($barStepExecution);
        static::assertNotSame($barContext, $barContext2);
    }

    /**
     * @param string $alias
     * @return MockObject|StepExecution
     */
    protected function createStepExecution($alias = null)
    {
        $stepExecution = $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();

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
