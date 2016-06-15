<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class ReindexCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new ReindexCommand();
    }

    public function testShouldReindexAllIfClassArgumentWasNotProvided()
    {
        $indexer = $this->createSearchIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('reindex')
            ->with($this->isNull())
        ;

        $container = new Container();
        $container->set('oro_search.async.indexer', $indexer);

        $command = new ReindexCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldReindexOnlySingleClassIfClassArgumentExists()
    {
        $indexer = $this->createSearchIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('reindex')
            ->with('class-name')
        ;

        $container = new Container();
        $container->set('oro_search.async.indexer', $indexer);

        $command = new ReindexCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'class' => 'class-name'
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|IndexerInterface
     */
    private function createSearchIndexerMock()
    {
        return $this->getMock(IndexerInterface::class);
    }
}
