<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Doctrine\Common\Cache\CacheProvider;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;

class LogLevelConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $loggerCache;

    private LogLevelConfig $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->loggerCache = $this->createMock(CacheProvider::class);

        $this->config = new LogLevelConfig(
            $this->loggerCache,
            $this->configManager,
            1,
            'warning'
        );
    }

    public function testGetMinLevelApplicationIsNotInstalled()
    {
        $config = new LogLevelConfig(
            $this->loggerCache,
            $this->configManager,
            null,
            'warning'
        );
        $this->loggerCache->expects(self::once())
            ->method('fetch')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(false);

        $this->configManager->expects($this->never())->method('get');

        $this->assertEquals(300, $config->getMinLevel());
    }

    public function testGetMinLevelWithCache()
    {
        $this->loggerCache->expects(self::once())
            ->method('fetch')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::WARNING);

        $this->configManager->expects($this->never())->method('get');

        $this->assertEquals(Logger::WARNING, $this->config->getMinLevel());
    }

    public function testGetMinLevelWithoutCache()
    {
        $this->loggerCache->expects(self::once())
            ->method('fetch')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(false);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_logger.detailed_logs_end_timestamp'],
                ['oro_logger.detailed_logs_level']
            )
            ->willReturnOnConsecutiveCalls(
                time() + 500,
                'info'
            );

        $this->config->isActive();
        $this->assertEquals(Logger::INFO, $this->config->getMinLevel());
    }

    public function testIsActiveTrue()
    {
        $this->loggerCache->expects(self::once())
            ->method('fetch')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::INFO);

        $this->configManager->expects($this->never())->method('get');

        $this->assertTrue($this->config->isActive());
    }

    public function testIsActiveFalse()
    {
        $this->loggerCache->expects(self::once())
            ->method('fetch')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::WARNING);

        $this->configManager->expects($this->never())->method('get');

        $this->assertFalse($this->config->isActive());
    }

    public function testReset()
    {
        $this->loggerCache->expects(self::exactly(2))
            ->method('fetch')
            ->with(LogLevelConfig::CACHE_KEY)
            ->willReturn(Logger::WARNING);

        $this->configManager->expects($this->never())->method('get');

        $this->assertEquals(Logger::WARNING, $this->config->getMinLevel());
        $this->config->reset();
        $this->assertEquals(Logger::WARNING, $this->config->getMinLevel());
    }
}
