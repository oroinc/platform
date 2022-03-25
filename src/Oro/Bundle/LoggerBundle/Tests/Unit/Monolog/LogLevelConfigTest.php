<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class LogLevelConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ArrayAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $loggerCache;

    private LogLevelConfig $config;

    private ApplicationState $applicationState;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->loggerCache = $this->createMock(ArrayAdapter::class);
        $this->cacheItemMock = $this->createMock(ItemInterface::class);
        $this->applicationState = $this->createMock(ApplicationState::class);

        $this->applicationState->method('isInstalled')->willReturn(true);

        $this->config = new LogLevelConfig(
            $this->loggerCache,
            $this->configManager,
            $this->applicationState,
            Logger::WARNING
        );
    }

    public function testGetMinLevelApplicationIsNotInstalled(): void
    {
        $applicationState = $this->createMock(ApplicationState::class);
        $applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $config = new LogLevelConfig(
            $this->loggerCache,
            $this->configManager,
            $applicationState,
            Logger::WARNING
        );
        $this->loggerCache->expects(self::once())
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturnCallback(function ($logLevel, $callable) {
                return $callable($this->cacheItemMock);
            });

        $this->configManager->expects($this->never())->method('get');

        $this->assertEquals(Logger::WARNING, $config->getMinLevel());
    }

    public function testGetMinLevelWithCache(): void
    {
        $this->loggerCache->expects($this->once())
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::WARNING);

        $this->configManager->expects($this->never())->method('get');

        $this->assertEquals(Logger::WARNING, $this->config->getMinLevel());
    }

    public function testGetMinLevelWithoutCache(): void
    {
        $this->loggerCache->expects(self::once())
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturnCallback(function ($logLevel, $callable) {
                return $callable($this->cacheItemMock);
            });

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_logger.detailed_logs_end_timestamp'],
                ['oro_logger.detailed_logs_level']
            )
            ->willReturnOnConsecutiveCalls(
                time() + 500,
                Logger::INFO
            );

        $this->config->isActive();
        $this->assertEquals(Logger::INFO, $this->config->getMinLevel());
    }

    public function testIsActiveTrue(): void
    {
        $this->loggerCache->expects(self::once())
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::INFO);

        $this->configManager->expects($this->never())->method('get');

        $this->assertTrue($this->config->isActive());
    }

    public function testIsActiveFalse(): void
    {
        $this->loggerCache->expects(self::once())
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::WARNING);

        $this->configManager->expects($this->never())->method('get');

        $this->assertFalse($this->config->isActive());
    }

    public function testReset(): void
    {
        $this->loggerCache->expects(self::exactly(2))
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::WARNING);

        $this->configManager->expects($this->never())->method('get');

        $this->assertEquals(Logger::WARNING, $this->config->getMinLevel());
        $this->config->reset();
        $this->assertEquals(Logger::WARNING, $this->config->getMinLevel());
    }

    public function testAntiRecursionFlagHandling(): void
    {
        $applicationState = $this->createMock(ApplicationState::class);

        $config = new LogLevelConfig(
            $this->loggerCache,
            $this->configManager,
            $applicationState,
            Logger::WARNING
        );

        $applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturnCallback(function () use ($config) {
                // single(and only available in this way) recursion loop triggering
                $config->getMinLevel();
                return true;
            });

        // expected 'get' calls count detects recursion loop
        $this->loggerCache->expects(self::exactly(2))
            ->method('get')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturnCallback(function ($logLevel, $callable) {
                return $callable($this->cacheItemMock);
            });

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_logger.detailed_logs_end_timestamp'],
                ['oro_logger.detailed_logs_level']
            )
            ->willReturnOnConsecutiveCalls(
                time() + 500,
                Logger::INFO
            );

        $config->getMinLevel();
    }
}
