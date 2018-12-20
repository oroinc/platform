<?php
namespace Oro\Bundle\SearchBundle\Tests\Unit\Command;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class ReindexCommandTest extends \PHPUnit\Framework\TestCase
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
        $container->set('oro_search.search.engine.indexer', $indexer);

        $command = new ReindexCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertContains('Started reindex task for all mapped entities', $tester->getDisplay());
        $this->assertContains('Reindex finished successfully', $tester->getDisplay());
    }

    public function testShouldReindexOnlySingleClassIfClassArgumentExists()
    {
        $shortClassName = 'Class:Name';
        $fullClassName = 'Class\Entity\Name';

        $indexer = $this->createSearchIndexerMock();
        $indexer
            ->expects($this->once())
            ->method('reindex')
            ->with($fullClassName)
        ;

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($shortClassName)
            ->willReturn($fullClassName);

        $container = new Container();
        $container->set('oro_search.search.engine.indexer', $indexer);
        $container->set('oro_entity.doctrine_helper', $doctrineHelper);

        $command = new ReindexCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'class' => $shortClassName
        ]);

        $this->assertContains(sprintf('Started reindex task for "%s" entity', $fullClassName), $tester->getDisplay());
    }

    public function testShouldReindexAsynchronouslyIfParameterSpecified()
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
        $tester->execute(['--scheduled' => true]);

        $this->assertContains('Started reindex task for all mapped entities', $tester->getDisplay());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|IndexerInterface
     */
    private function createSearchIndexerMock()
    {
        return $this->createMock(IndexerInterface::class);
    }
}
