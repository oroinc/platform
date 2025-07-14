<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;
use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityWriterTest extends TestCase
{
    protected EntityManager&MockObject $entityManager;
    protected DoctrineHelper&MockObject $doctrineHelper;
    protected EntityDetachFixer&MockObject $detachFixer;
    protected ContextRegistry&MockObject $contextRegistry;
    protected EntityWriter $writer;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->detachFixer = $this->createMock(EntityDetachFixer::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->writer = new EntityWriter(
            $this->doctrineHelper,
            $this->detachFixer,
            $this->contextRegistry
        );
    }

    /**
     * @dataProvider configurationProvider
     */
    public function testWrite(array $configuration): void
    {
        $fooItem = $this->createMock(\stdClass::class);
        $barItem = $this->createMock(\ArrayObject::class);

        $this->detachFixer->expects($this->exactly(2))
            ->method('fixEntityAssociationFields')
            ->withConsecutive([$fooItem, 1], [$barItem, 1]);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive([$fooItem], [$barItem]);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $stepExecution = $this->createMock(StepExecution::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        if (empty($configuration[EntityWriter::SKIP_CLEAR])) {
            $this->entityManager->expects($this->once())
                ->method('clear');
        }

        $this->writer->setStepExecution($stepExecution);
        $this->writer->write([$fooItem, $barItem]);
    }

    public function testWriteException(): void
    {
        $item = $this->createMock(\stdClass::class);

        $this->detachFixer->expects($this->once())
            ->method('fixEntityAssociationFields')
            ->with($item, 1);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($item);

        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception());

        $stepExecution = $this->createMock(StepExecution::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->any())
            ->method('getConfiguration')
            ->willReturn(['entityName' => \stdClass::class]);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);

        $this->expectException(\Exception::class);

        $this->writer->write([$item]);
    }

    public function testWriteDatabaseExceptionDeadlock(): void
    {
        $exception = $this->createMock(DeadlockException::class);
        $item = $this->createMock(\stdClass::class);

        $this->detachFixer->expects($this->once())
            ->method('fixEntityAssociationFields')
            ->with($item, 1);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($item);

        $this->entityManager->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $stepExecution = $this->createMock(StepExecution::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->any())
            ->method('getConfiguration')
            ->willReturn(['entityName' => \stdClass::class]);

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);

        $context->expects($this->once())
            ->method('setValue')
            ->with('deadlockDetected', true);

        $this->writer->write([$item]);
    }

    public function testMissingClassName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('entityName not resolved');

        $stepExecution = $this->createMock(StepExecution::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);
        $this->writer->write([]);
    }

    public function testClassResolvedOnce(): void
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->once())
            ->method('getConfiguration')
            ->willReturn(['entityName' => \stdClass::class]);

        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        $this->writer->setStepExecution($stepExecution);
        $this->writer->write([]);

        // trigger detection twice
        $this->writer->write([]);
    }

    public function configurationProvider(): array
    {
        return [
            'no clear flag'    => [[]],
            'clear flag false' => [[EntityWriter::SKIP_CLEAR => false]],
            'clear flag true'  => [[EntityWriter::SKIP_CLEAR => true]],
            'className from config'  => [[EntityWriter::SKIP_CLEAR => true, 'entityName' => \stdClass::class]],
        ];
    }
}
