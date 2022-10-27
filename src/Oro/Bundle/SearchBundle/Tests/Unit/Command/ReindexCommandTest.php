<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ReindexCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $asyncIndexer;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $syncIndexer;

    /** @var ReindexCommand */
    private $command;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->asyncIndexer = $this->createMock(IndexerInterface::class);
        $this->syncIndexer = $this->createMock(IndexerInterface::class);

        $this->command = new ReindexCommand($this->doctrineHelper, $this->asyncIndexer, $this->syncIndexer);
    }

    public function testShouldReindexAllIfClassArgumentWasNotProvided()
    {
        $this->syncIndexer->expects($this->once())
            ->method('reindex')
            ->with($this->isNull());

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        self::assertStringContainsString('Started reindex task for all mapped entities', $tester->getDisplay());
        self::assertStringContainsString('Reindex finished successfully', $tester->getDisplay());
    }

    public function testShouldReindexOnlySingleClassIfClassArgumentExists()
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

    public function testShouldReindexAsynchronouslyIfParameterSpecified()
    {
        $this->asyncIndexer->expects($this->once())
            ->method('reindex')
            ->with($this->isNull());

        $tester = new CommandTester($this->command);
        $tester->execute(['--scheduled' => true]);

        self::assertStringContainsString('Started reindex task for all mapped entities', $tester->getDisplay());
    }
}
