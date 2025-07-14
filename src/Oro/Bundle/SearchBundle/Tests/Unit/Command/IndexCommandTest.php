<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Command\IndexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IndexCommandTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private IndexerInterface&MockObject $indexer;
    private IndexCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->indexer = $this->createMock(IndexerInterface::class);

        $this->command = new IndexCommand($this->doctrine, $this->indexer);
    }

    public function testShouldThrowExceptionIfClassArgumentIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "class")');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'identifiers' => '123',
        ]);
    }

    public function testShouldThrowExceptionIfIdentifiersArgumentIsMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "identifiers")');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'class' => 'class-name',
        ]);
    }

    public function testShouldThrowExceptionIfEntityManagerWasNotFoundForClass(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity manager was not found for class: "class-name"');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->willReturn(null);
        $this->indexer->expects($this->never())
            ->method('save');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'class' => 'class-name',
            'identifiers' => ['id'],
        ]);
    }

    public function testShouldIndexEntities(): void
    {
        $entity = new \stdClass();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getReference')
            ->with('class-name', 'id')
            ->willReturn($entity);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->willReturn($em);

        $this->indexer->expects($this->once())
            ->method('save')
            ->with([$entity]);

        $tester = new CommandTester($this->command);
        $tester->execute([
            'class' => 'class-name',
            'identifiers' => ['id'],
        ]);

        self::assertStringContainsString('Started index update for entities.', $tester->getDisplay());
    }
}
