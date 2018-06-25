<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Writer;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\IntegrationBundle\Event\WriterErrorEvent;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Psr\Log\LoggerInterface;

class PersistentBatchWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $contextRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var PersistentBatchWriter */
    protected $writer;

    /** @var LoggerInterface */
    protected $logger;

    protected function setUp()
    {
        $this->registry        = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->contextRegistry = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextRegistry');
        $this->logger          = $this->createMock('Psr\Log\LoggerInterface');
        $this->entityManager   = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->registry->expects($this->any())->method('getManager')
            ->will($this->returnValue($this->entityManager));
    }

    /**
     * @param array $configuration
     *
     * @dataProvider configurationProvider
     */
    public function testWrite(array $configuration)
    {
        $this->entityManager->expects($this->once())->method('beginTransaction');

        $fooItem = $this->createMock(\stdClass::class);
        $barItem = $this->createMock(\ArrayObject::class);

        $this->entityManager->expects($this->exactly(2))->method('persist')
            ->with($this->logicalOr($this->equalTo($fooItem), $this->equalTo($barItem)));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectGetJobName($stepExecution);

        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $context->expects($this->once())
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

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
    public function testWriteRollback($couldBeSkipped)
    {
        $fooItem = $this->createMock(\stdClass::class);
        $barItem = $this->createMock(\ArrayObject::class);

        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->exactly(2))->method('persist')
            ->with($this->logicalOr($this->equalTo($fooItem), $this->equalTo($barItem)));
        $this->entityManager->expects($this->once())
            ->method('flush')
            ->will($this->throwException(new \Exception('error')));
        $this->entityManager->expects($this->once())->method('rollback');

        $this->entityManager->expects($this->once())->method('isOpen')
            ->will($this->returnValue(false));

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()->getMock();

        $this->expectGetJobName($stepExecution);

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->will(
                $this->returnCallback(
                    function ($eventName, WriterErrorEvent $event) use ($couldBeSkipped) {
                        $event->setCouldBeSkipped($couldBeSkipped);
                    }
                )
            );

        if ($couldBeSkipped) {
            $context = $this->getCouldBeSkippedExpects($stepExecution);
        } else {
            $this->expectException('Exception');
        }

        $writer = $this->getWriter();
        $writer->setStepExecution($stepExecution);
        $writer->write([$fooItem, $barItem]);

        if ($couldBeSkipped) {
            $this->assertEquals(2, $context->getErrorEntriesCount());
            $this->assertCount(1, $context->getErrors());
        }
    }

    /**
     * @return array
     */
    public function writeErrorProvider()
    {
        return [
            'could be skipped'     => [true],
            'could not be skipped' => [false],
        ];
    }

    /**
     * @return array
     */
    public function configurationProvider()
    {
        return [
            'no clear flag'    => [[]],
            'clear flag false' => [[EntityWriter::SKIP_CLEAR => false]],
            'clear flag true'  => [[EntityWriter::SKIP_CLEAR => true]],
        ];
    }

    /**
     * @param $stepExecution
     *
     * @return Context
     */
    protected function getCouldBeSkippedExpects($stepExecution)
    {
        $context = new Context(['error_entries_count' => 0]);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $this->expectException('Akeneo\Bundle\BatchBundle\Item\InvalidItemException');

        return $context;
    }

    protected function expectGetJobName($stepExecution)
    {
        $jobInstance  = new JobInstance(null, null, 'test');
        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);

        $stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->will($this->returnValue($jobExecution));
    }

    /**
     * @param bool $isManagerOpen
     *
     * @return PersistentBatchWriter
     */
    protected function getWriter($isManagerOpen = true)
    {
        $this->entityManager->expects($this->at(0))
            ->method('isOpen')
            ->will($this->returnValue($isManagerOpen));

        return new PersistentBatchWriter(
            $this->registry,
            $this->eventDispatcher,
            $this->contextRegistry,
            $this->logger
        );
    }
}
