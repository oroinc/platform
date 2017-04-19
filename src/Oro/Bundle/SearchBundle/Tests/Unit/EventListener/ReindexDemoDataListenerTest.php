<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataListener;

class ReindexDemoDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $indexer;

    /** @var ReindexDemoDataListener */
    private $listener;

    public function setUp()
    {
        $this->indexer = $this->getMockBuilder(IndexerInterface::class)->getMock();
        $this->listener = new ReindexDemoDataListener($this->indexer);
    }

    /**
     * @dataProvider dataProviderForNotRunCase
     * @param string $commandName
     * @param int    $exitCode
     * @param string $fixturesType
     */
    public function testWillNotRunWhenNotAllRequirementsSatisfied($commandName, $exitCode, $fixturesType = 'not-a-demo')
    {
        $this->listener->afterExecute($this->getEvent($commandName, $exitCode, $fixturesType));
    }

    public function testDispatchReindexationEvent()
    {
        $this->indexer->expects($this->once())->method('reindex');
        $this->listener->afterExecute($this->getEvent(
            LoadDataFixturesCommand::COMMAND_NAME,
            0,
            LoadDataFixturesCommand::DEMO_FIXTURES_TYPE
        ));
    }

    /**
     * @return array
     */
    public function dataProviderForNotRunCase()
    {
        return [
            'not supported command' => [
                'non-supported', 0
            ],
            'wrong exit code #1'    => [
                LoadDataFixturesCommand::COMMAND_NAME, 1
            ],
            'wrong exit code #2'    => [
                LoadDataFixturesCommand::COMMAND_NAME, -1
            ],
            'not a demo fixture'    => [
                LoadDataFixturesCommand::COMMAND_NAME, 0,
            ],
        ];
    }

    /**
     * @param string $commandName
     * @param int    $exitCode
     * @param string $fixturesType
     *
     * @return ConsoleTerminateEvent
     */
    private function getEvent($commandName, $exitCode, $fixturesType = 'not-a-demo')
    {
        /** @var Command|\PHPUnit_Framework_MockObject_MockObject $command */
        $command = $this->getMockBuilder(Command::class)->disableOriginalConstructor()->getMock();
        $input   = $this->getMockBuilder(InputInterface::class)->getMock();
        $output  = $this->getMockBuilder(OutputInterface::class)->getMock();

        $command->expects($this->any())->method('getName')->willReturn($commandName);
        $input->expects($this->any())->method('getOption')->with('fixtures-type')->willReturn($fixturesType);

        return new ConsoleTerminateEvent($command, $input, $output, $exitCode);
    }
}
