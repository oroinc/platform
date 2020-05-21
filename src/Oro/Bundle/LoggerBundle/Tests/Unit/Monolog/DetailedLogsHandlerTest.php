<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Doctrine\Common\Cache\CacheProvider;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler;

class DetailedLogsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var HandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $nestedHandler;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $loggerCache;

    /** @var DetailedLogsHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigManager::class);
        $this->loggerCache = $this->createMock(CacheProvider::class);

        $this->handler = new DetailedLogsHandler(
            $this->config,
            $this->loggerCache,
            1,
            'warning'
        );
    }

    public function testIsHandlingApplicationIsNotInstalled()
    {
        $this->loggerCache->expects($this->once())
            ->method('fetch')
            ->with(Configuration::LOGS_LEVEL_KEY)
            ->willReturn(false);

        $this->loggerCache->expects($this->once())
            ->method('fetch')
            ->with(Configuration::LOGS_LEVEL_KEY)
            ->willReturn(false);

        $this->config->expects($this->never())->method('get');

        $handler = new DetailedLogsHandler(
            $this->config,
            $this->loggerCache,
            0,
            'warning'
        );

        $this->assertTrue($handler->isHandling(['level' => Logger::WARNING]));
    }

    private function configureMethods()
    {
        $this->loggerCache->expects($this->once())
            ->method('fetch')
            ->with(Configuration::LOGS_LEVEL_KEY)
            ->willReturn('warning');

        $this->config->expects($this->never())->method('get');
    }

    public function testIsHandlingWithCache()
    {
        $this->configureMethods();

        $this->assertTrue($this->handler->isHandling(['level' => Logger::WARNING]));
    }

    public function testHandleException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Trying to execute method `Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler::write`'
            . ' which requires Handler to be set.'
        );

        $this->configureMethods();

        $record = [
            'message' => 'Some error message',
            'context' => [],
            'level' => Logger::WARNING,
            'level_name' => Logger::WARNING,
            'channel' => 'chanelName',
            'datetime' => new \DateTime(),
            'extra' => [],
        ];

        $this->handler->handle($record);
    }

    public function testHandle()
    {
        $this->configureMethods();

        $record = [
            'message' => 'Some error message',
            'context' => [],
            'level' => Logger::WARNING,
            'level_name' => Logger::WARNING,
            'channel' => 'chanelName',
            'datetime' => new \DateTime(),
            'extra' => [],
        ];

        $this->nestedHandler = $this->getMockBuilder(HandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->nestedHandler->expects($this->once())
            ->method('handle');

        $this->handler->setHandler($this->nestedHandler);
        $this->handler->handle($record);
    }
}
