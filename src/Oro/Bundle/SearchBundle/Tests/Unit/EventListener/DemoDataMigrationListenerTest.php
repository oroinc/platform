<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

use Oro\Bundle\SearchBundle\EventListener\DemoDataMigrationListener;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;

class DemoDataMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchIndexer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchListener;

    /**
     * @var DemoDataMigrationListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->searchIndexer = $this->getMock(IndexerInterface::class);

        $this->searchListener = $this->getMockBuilder('Oro\Bundle\SearchBundle\EventListener\IndexListener')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new DemoDataMigrationListener($this->searchIndexer, $this->searchListener);
    }

    /**
     * @param string $commandClass
     * @param string|null $fixturesType
     * @dataProvider consoleDataProvider
     */
    public function testOnConsoleCommand($commandClass, $fixturesType = null)
    {
        if ($fixturesType == LoadDataFixturesCommand::DEMO_FIXTURES_TYPE) {
            $this->searchListener->expects($this->once())
                ->method('setEnabled')
                ->with(false);
        } else {
            $this->searchListener->expects($this->never())
                ->method('setEnabled');
        }

        list($command, $input, $output) = $this->prepareEventData($commandClass, $fixturesType);
        $this->listener->onConsoleCommand(new ConsoleCommandEvent($command, $input, $output));
    }

    /**
     * @param string $commandClass
     * @param string|null $fixturesType
     * @param int $exitCode
     * @dataProvider consoleDataProvider
     */
    public function testOnConsoleTerminate($commandClass, $fixturesType = null, $exitCode = 0)
    {
        if ($fixturesType == LoadDataFixturesCommand::DEMO_FIXTURES_TYPE) {
            if ($exitCode === 0) {
                $this->searchIndexer->expects($this->once())
                    ->method('reindex')
                    ->with();
            } else {
                $this->searchIndexer->expects($this->never())
                    ->method('reindex');
            }
            $this->searchListener->expects($this->once())
                ->method('setEnabled')
                ->with(true);
        } else {
            $this->searchIndexer->expects($this->never())
                ->method('reindex');
            $this->searchListener->expects($this->never())
                ->method('setEnabled');
        }

        list($command, $input, $output) = $this->prepareEventData($commandClass, $fixturesType);
        $this->listener->onConsoleTerminate(new ConsoleTerminateEvent($command, $input, $output, $exitCode));
    }

    /**
     * @return array
     */
    public function consoleDataProvider()
    {
        return [
            'not a demo migration command' => [
                'commandClass' => 'Oro\Bundle\SearchBundle\Command\ReindexCommand',
            ],
            'main migration command' => [
                'commandClass' => 'Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand',
                'fixturesType' => LoadDataFixturesCommand::MAIN_FIXTURES_TYPE,
            ],
            'demo migration command' => [
                'commandClass' => 'Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand',
                'fixturesType' => LoadDataFixturesCommand::DEMO_FIXTURES_TYPE,
            ],
            'invalid demo migration command' => [
                'commandClass' => 'Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand',
                'fixturesType' => LoadDataFixturesCommand::DEMO_FIXTURES_TYPE,
                'exitCode'     => 1
            ],
        ];
    }

    /**
     * @param string $commandClass
     * @param string|null $fixturesType
     * @return array
     */
    protected function prepareEventData($commandClass, $fixturesType = null)
    {
        $command = $this->getMockBuilder($commandClass)
            ->disableOriginalConstructor()
            ->getMock();

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        if ($fixturesType) {
            $input->expects($this->any())
                ->method('hasOption')
                ->with('fixtures-type')
                ->willReturn(true);
            $input->expects($this->any())
                ->method('getOption')
                ->with('fixtures-type')
                ->will($this->returnValue($fixturesType));
        }

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        return [$command, $input, $output];
    }
}
