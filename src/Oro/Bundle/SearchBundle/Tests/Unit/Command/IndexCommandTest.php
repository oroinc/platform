<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\SearchBundle\Command\IndexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class IndexCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new IndexCommand();
    }

    public function testShouldThrowExceptionIfClassArgumentIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "class")');

        $command = new IndexCommand();

        $tester = new CommandTester($command);
        $tester->execute([
            'identifiers' => '123',
        ]);
    }

    public function testShouldThrowExceptionIfIdentifiersArgumentIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "identifiers")');

        $command = new IndexCommand();

        $tester = new CommandTester($command);
        $tester->execute([
            'class' => 'class-name',
        ]);
    }

    public function testShouldThrowExceptionIfEntityManagerWasNotFoundForClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity manager was not found for class: "class-name"');

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->will($this->returnValue(null))
        ;

        $indexer = $this->createSearchIndexerMock();

        $container = new Container();
        $container->set('oro_search.async.indexer', $indexer);
        $container->set('doctrine', $doctrine);

        $command = new IndexCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'class' => 'class-name',
            'identifiers' => ['id'],
        ]);
    }

    public function testShouldIndexEntities()
    {
        $entity = new \stdClass();

        $em = $this->createEntityMangerMock();
        $em
            ->expects($this->once())
            ->method('getReference')
            ->with('class-name', 'id')
            ->will($this->returnValue($entity))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('class-name')
            ->will($this->returnValue($em))
        ;

        $indexer = $this->createSearchIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('save')
            ->with([$entity])
        ;

        $container = new Container();
        $container->set('oro_search.async.indexer', $indexer);
        $container->set('doctrine', $doctrine);

        $command = new IndexCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'class' => 'class-name',
            'identifiers' => ['id'],
        ]);

        $this->assertContains('Started index update for entities.', $tester->getDisplay());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManager
     */
    private function createEntityMangerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RegistryInterface
     */
    private function createDoctrineMock()
    {
        return $this->createMock(RegistryInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|IndexerInterface
     */
    private function createSearchIndexerMock()
    {
        return $this->createMock(IndexerInterface::class);
    }
}
