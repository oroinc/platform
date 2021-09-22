<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Exception\InvalidItemException;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\IntegrationBundle\Event\WriterErrorEvent;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PersistentBatchWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->registry->expects($this->any())->method('getManager')
            ->willReturn($this->entityManager);
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testWrite(array $configuration): void
    {
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $fooItem = $this->createMock(\stdClass::class);
        $barItem = $this->createMock(\ArrayObject::class);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr($this->equalTo($fooItem), $this->equalTo($barItem)));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $stepExecution = $this->createMock(StepExecution::class);

        $this->expectGetJobName($stepExecution);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $writer = $this->getWriter();
        $writer->setStepExecution($stepExecution);
        $writer->write([$fooItem, $barItem]);
    }

    /**
     * @param bool $couldBeSkipped
     *
     * @dataProvider writeErrorProvider
     */
    public function testWriteRollback($couldBeSkipped): void
    {
        $fooItem = $this->createMock(\stdClass::class);
        $barItem = $this->createMock(\ArrayObject::class);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->logicalOr($this->equalTo($fooItem), $this->equalTo($barItem)));
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception('error'));
        $this->entityManager->expects($this->once())
            ->method('rollback');

        $this->entityManager->expects($this->once())
            ->method('isOpen')
            ->willReturn(false);

        $stepExecution = $this->createMock(StepExecution::class);

        $this->expectGetJobName($stepExecution);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (WriterErrorEvent $event) use ($couldBeSkipped) {
                $event->setCouldBeSkipped($couldBeSkipped);

                return $event;
            });

        if ($couldBeSkipped) {
            $context = $this->getCouldBeSkippedExpects($stepExecution);
        } else {
            $this->expectException(\Exception::class);
        }

        $writer = $this->getWriter();
        $writer->setStepExecution($stepExecution);
        $writer->write([$fooItem, $barItem]);

        if ($couldBeSkipped) {
            $this->assertEquals(2, $context->getErrorEntriesCount());
            $this->assertCount(1, $context->getErrors());
        }
    }

    public function writeErrorProvider(): array
    {
        return [
            'could be skipped'     => [true],
            'could not be skipped' => [false],
        ];
    }

    public function configurationProvider(): array
    {
        return [
            'no clear flag'    => [[]],
            'clear flag false' => [[EntityWriter::SKIP_CLEAR => false]],
            'clear flag true'  => [[EntityWriter::SKIP_CLEAR => true]],
        ];
    }

    private function getCouldBeSkippedExpects(StepExecution $stepExecution): Context
    {
        $context = new Context(['error_entries_count' => 0]);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->expectException(InvalidItemException::class);

        return $context;
    }

    /**
     * @param StepExecution|\PHPUnit\Framework\MockObject\MockObject $stepExecution
     */
    private function expectGetJobName($stepExecution): void
    {
        $jobInstance = new JobInstance(null, null, 'test');
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);

        $stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($jobExecution);
    }

    private function getWriter(bool $isManagerOpen = true): PersistentBatchWriter
    {
        $this->entityManager->expects($this->any())
            ->method('isOpen')
            ->willReturn($isManagerOpen);

        return new PersistentBatchWriter(
            $this->registry,
            $this->eventDispatcher,
            $this->contextRegistry,
            $this->logger
        );
    }
}
