<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

class ContextRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContextRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new ContextRegistry();
    }

    public function testGetByStepExecution()
    {
        $fooStepExecution = $this->createStepExecution();
        $fooContext = $this->registry->getByStepExecution($fooStepExecution);
        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext', $fooContext);
        $this->assertAttributeEquals($fooStepExecution, 'stepExecution', $fooContext);
        $this->assertSame($fooContext, $this->registry->getByStepExecution($fooStepExecution));

        $barStepExecution = $this->createStepExecution('job2');
        $barContext = $this->registry->getByStepExecution($barStepExecution);
        $this->assertNotSame($barContext, $fooContext);

        /** @var \PHPUnit\Framework\MockObject\MockObject|JobInstance $jobInstance */
        $jobInstance = $this->createMock('Akeneo\Bundle\BatchBundle\Entity\JobInstance');
        $jobInstance->expects($this->any())
            ->method('getAlias')
            ->will($this->returnValue('job2'));
        $this->registry->clear($jobInstance);
        $barContext2 = $this->registry->getByStepExecution($barStepExecution);
        $this->assertNotSame($barContext, $barContext2);
    }

    /**
     * @param string $alias
     * @return \PHPUnit\Framework\MockObject\MockObject|StepExecution
     */
    protected function createStepExecution($alias = null)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        if ($alias) {
            $jobExecution = $this->createMock('Akeneo\Bundle\BatchBundle\Entity\JobExecution');
            $jobInstance = $this->createMock('Akeneo\Bundle\BatchBundle\Entity\JobInstance');
            $jobExecution->expects($this->any())
                ->method('getJobInstance')
                ->will($this->returnValue($jobInstance));

            $stepExecution->expects($this->any())
                ->method('getJobExecution')
                ->will($this->returnValue($jobExecution));

            $jobInstance->expects($this->any())
                ->method('getAlias')
                ->will($this->returnValue($alias));
        }

        return $stepExecution;
    }
}
