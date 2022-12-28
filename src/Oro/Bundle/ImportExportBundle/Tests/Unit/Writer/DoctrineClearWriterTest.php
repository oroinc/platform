<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;

class DoctrineClearWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var DoctrineClearWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->writer = new DoctrineClearWriter($this->registry, $this->contextRegistry);
    }

    public function testWrite(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('clear');

        $this->registry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $this->writer->write([]);
    }

    public function testWriteWithoutDoctrineClear(): void
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->once())
            ->method('clear');

        $this->registry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $stepExecution = $this->createMock(StepExecution::class);
        $context = $this->createMock(StepExecutionProxyContext::class);
        $context
            ->expects($this->once())
            ->method('getValue')
            ->with(DoctrineClearWriter::SKIP_CLEAR)
            ->willReturn(false);
        $this->contextRegistry
            ->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);
        $this->writer->write([]);
    }

    public function testWriteWithDoctrineClear(): void
    {
        $this->registry
            ->expects($this->never())
            ->method('getManager');

        $stepExecution = $this->createMock(StepExecution::class);
        $context = $this->createMock(StepExecutionProxyContext::class);
        $context
            ->expects($this->once())
            ->method('getValue')
            ->with(DoctrineClearWriter::SKIP_CLEAR)
            ->willReturn(true);
        $this->contextRegistry
            ->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);
        $this->writer->write([]);
    }
}
