<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler;

class DetailedLogsHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var HandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nestedHandler;

    /**
     * @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var DetailedLogsHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->config = $this->getMockBuilder(ConfigManager::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->nestedHandler = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMockBuilder(ContainerInterface::class)->disableOriginalConstructor()->getMock();
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_config.user')
            ->willReturn($this->config);
        $this->container->expects($this->any())
            ->method('has')
            ->with('oro_config.user')
            ->willReturn(true);

        $this->handler = new DetailedLogsHandler();
        $this->handler->setHandler($this->nestedHandler);
        $this->handler->setContainer($this->container);
    }

    /**
     * @dataProvider handlingDataProvider
     *
     * @param bool   $installed
     * @param string $logsLevel
     * @param int    $delay
     * @param string $defaultLogLevel
     * @param int    $recordLevel
     * @param bool   $expected
     */
    public function testIsHandling($installed, $logsLevel, $delay, $defaultLogLevel, $recordLevel, $expected)
    {
        if (!$installed) {
            $this->config
                ->expects($this->never())
                ->method('get');
        } else {
            $time = time();
            $this->config->expects($this->at(0))
                ->method('get')
                ->with('oro_logger.detailed_logs_end_timestamp')
                ->willReturn($time + $delay);
            if ($time + $delay > $time) {
                $this->config->expects($this->at(1))
                    ->method('get')
                    ->with('oro_logger.detailed_logs_level')
                    ->willReturn($logsLevel);
            }
        }

        $this->container
            ->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['oro_logger.detailed_logs_default_level', $defaultLogLevel],
                ['installed', $installed],
            ]);

        $this->assertEquals($expected, $this->handler->isHandling(['level' => $recordLevel]));
    }

    public function testNoRecordsArePassedToNestedHandlerWhenEndTimestampCheckFails()
    {
        $this->nestedHandler->expects($this->never())->method('handleBatch');

        $this->handler->close();
    }

    public function testClose()
    {
        $this->container
            ->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(true);

        $this->container
            ->expects($this->exactly(2))
            ->method('getParameter')
            ->willReturnMap([
                ['oro_logger.detailed_logs_default_level', 'debug'],
                ['installed', true],
            ]);

        $this->config->expects($this->at(0))
            ->method('get')
            ->with('oro_logger.detailed_logs_end_timestamp')
            ->willReturn(time() + 3600);

        $this->config->expects($this->at(1))
            ->method('get')
            ->with('oro_logger.detailed_logs_level')
            ->willReturn('warning');

        $this->nestedHandler->expects($this->once())
            ->method('handleBatch')
            ->with([
                2 => ['level' => Logger::WARNING],
                3 => ['level' => Logger::ERROR],
                4 => ['level' => Logger::CRITICAL],
            ]);

        $this->handler->isHandling(['level' => Logger::DEBUG]);

        $this->handler->handle(['level' => Logger::DEBUG]);
        $this->handler->handle(['level' => Logger::INFO]);
        $this->handler->handle(['level' => Logger::WARNING]);
        $this->handler->handle(['level' => Logger::ERROR]);
        $this->handler->handle(['level' => Logger::CRITICAL]);

        $this->handler->close();
    }

    /**
     * @return array
     */
    public function handlingDataProvider()
    {
        return [
            'can handling' => [
                'installed' => true,
                'logsLevel' => 'debug',
                'delay' => 3600,
                'defaultLogsLevel' => 'debug',
                'recordLevel' =>  Logger::DEBUG,
                'expected' => true,
            ],
            'can\'t handling' => [
                'installed' => true,
                'logsLevel' => 'warning',
                'delay' => 3600,
                'defaultLogsLevel' => 'debug',
                'recordLevel' =>  Logger::DEBUG,
                'expected' => false,
            ],
            'can handling default' => [
                'installed' => true,
                'logsLevel' => null,
                'delay' => -3600,
                'defaultLogsLevel' => 'debug',
                'recordLevel' =>  Logger::DEBUG,
                'expected' => true,
            ],
            'can\'t handling default' => [
                'installed' => true,
                'logsLevel' => null,
                'delay' => -3600,
                'defaultLogsLevel' => 'warning',
                'recordLevel' =>  Logger::DEBUG,
                'expected' => false,
            ],
            'application is not installed' => [
                'installed' => false,
                'logsLevel' => 'warning',
                'delay' => 3600,
                'defaultLogsLevel' => 'warning',
                'recordLevel' =>  Logger::DEBUG,
                'expected' => false,
            ],
        ];
    }
}
