<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Doctrine\Common\Cache\CacheProvider;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DetailedLogsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $config;

    /**
     * @var HandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $nestedHandler;

    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
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
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMockBuilder(ContainerInterface::class)->disableOriginalConstructor()->getMock();
        $this->container->expects($this->any())
            ->method('has')
            ->with('oro_config.user')
            ->willReturn(true);

        $this->handler = new DetailedLogsHandler();
        $this->handler->setContainer($this->container);
    }

    public function testIsHandlingApplicationIsNotInstalled()
    {
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cacheProvider */
        $cacheProvider = $this->getMockBuilder(CacheProvider::class)->getMock();

        $cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(Configuration::LOGS_LEVEL_KEY)
            ->willReturn(false);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->willReturnMap([
                ['oro_logger.cache', 1, $cacheProvider],
            ]);

        $cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(Configuration::LOGS_LEVEL_KEY)
            ->willReturn(false);

        $this->config->expects($this->never())->method('get');

        $this->container
            ->expects($this->once())
            ->method('getParameter')
            ->with('oro_logger.detailed_logs_default_level')
            ->willReturn('warning');

        $this->assertTrue($this->handler->isHandling(['level' => Logger::WARNING]));
    }

    private function configureMethods()
    {
        /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject $cacheProvider */
        $cacheProvider = $this->getMockBuilder(CacheProvider::class)->getMock();

        $cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(Configuration::LOGS_LEVEL_KEY)
            ->willReturn('warning');

        $this->container
            ->expects($this->exactly(1))
            ->method('get')
            ->willReturnMap([
                ['oro_logger.cache', 1, $cacheProvider],
            ]);

        $this->config->expects($this->never())->method('get');
        $this->container->expects($this->never())->method('hasParameter');
        $this->container->expects($this->never())->method('getParameter');
    }

    public function testIsHandlingWithCache()
    {
        $this->configureMethods();

        $this->assertTrue($this->handler->isHandling(['level' => Logger::WARNING]));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Trying to execute method `Oro\Bundle\LoggerBundle\Monolog\DetailedLogsHandler::write` which requires Handler to be set.
     */
    // @codingStandardsIgnoreEnd
    public function testHandleException()
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
