<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SearchBundle\Command\IndexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class IndexCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $indexer;

    /** @var IndexCommand */
    private $command;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->indexer = $this->createMock(IndexerInterface::class);

        $this->command = new IndexCommand($this->doctrine, $this->indexer);
    }

    public function testShouldThrowExceptionIfClassArgumentIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "class")');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'identifiers' => '123',
        ]);
    }

    public function testShouldThrowExceptionIfIdentifiersArgumentIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "identifiers")');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'class' => 'class-name',
        ]);
    }

    public function testShouldThrowExceptionIfEntityManagerWasNotFoundForClass()
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

    public function testShouldIndexEntities()
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
