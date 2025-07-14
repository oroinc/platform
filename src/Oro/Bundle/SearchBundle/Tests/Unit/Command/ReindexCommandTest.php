<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ReindexCommandTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private IndexerInterface&MockObject $asyncIndexer;
    private IndexerInterface&MockObject $syncIndexer;
    private ReindexCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->asyncIndexer = $this->createMock(IndexerInterface::class);
        $this->syncIndexer = $this->createMock(IndexerInterface::class);

        $this->command = new ReindexCommand($this->doctrineHelper, $this->asyncIndexer, $this->syncIndexer);
    }

    public function testShouldReindexAllIfClassArgumentWasNotProvided(): void
    {
        $this->syncIndexer->expects($this->once())
            ->method('reindex')
            ->with($this->isNull());

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertStringContainsString('Started reindex task for all mapped entities', $tester->getDisplay());
        self::assertStringContainsString('Reindex finished successfully', $tester->getDisplay());
    }

    public function testShouldReindexOnlySingleClassIfClassArgumentExists(): void
    {
        $shortClassName = 'Class:Name';
        $fullClassName = 'Class\Entity\Name';

        $this->syncIndexer->expects($this->once())
            ->method('reindex')
            ->with($fullClassName);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($shortClassName)
            ->willReturn($fullClassName);

        $tester = new CommandTester($this->command);
        $tester->execute([
            'class' => $shortClassName
        ]);

        self::assertStringContainsString(
            sprintf('Started reindex task for "%s" entity', $fullClassName),
            $tester->getDisplay()
        );
    }

    public function testShouldReindexAsynchronouslyIfParameterSpecified(): void
    {
        $this->asyncIndexer->expects($this->once())
            ->method('reindex')
            ->with($this->isNull());

        $tester = new CommandTester($this->command);
        $tester->execute(['--scheduled' => true]);

        self::assertStringContainsString('Started reindex task for all mapped entities', $tester->getDisplay());
    }
}
