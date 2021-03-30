<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Doctrine\Common\Cache\CacheProvider;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Detects whether user-configurable log level is active and returns it as an integer
 */
class LogLevelConfig implements ResetInterface
{
    public const CACHE_KEY = 'configurable_monolog_level';

    private int $defaultLevel;

    private bool $loading = false;

    private CacheProvider $loggerCache;

    private ?ConfigManager $configManager;

    private bool $installed;

    private ?int $logLevel = null;

    public function __construct(
        CacheProvider $loggerCache,
        ?ConfigManager $configManager,
        ?string $installed,
        $defaultLevel = Logger::ERROR
    ) {
        $this->configManager = $configManager;
        $this->loggerCache = $loggerCache;
        $this->defaultLevel = Logger::toMonologLevel($defaultLevel);
        $this->installed = (bool)$installed;
    }

    public function isActive(): bool
    {
        return $this->getMinLevel() !== $this->defaultLevel;
    }

    public function getMinLevel(): int
    {
        if (null !== $this->logLevel) {
            return $this->logLevel;
        }

        $cacheValue = $this->loggerCache->fetch(self::CACHE_KEY);
        $this->logLevel = $cacheValue === false ? null : $cacheValue;
        if (null === $this->logLevel) {
            $this->logLevel = $this->defaultLevel;
            if (!$this->loading) {
                $this->loading = true;
                try {
                    $this->logLevel = $this->loadLogLevel();
                } finally {
                    $this->loading = false;
                }
            }
        }

        return $this->logLevel;
    }

    private function loadLogLevel(): int
    {
        $logLevel = $this->defaultLevel;
        $lifeTime = 0;
        if ($this->configManager && $this->installed) {
            $curTimestamp = time();
            $endTimestamp = $this->configManager
                ->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY));
            if (null !== $endTimestamp && $curTimestamp <= $endTimestamp) {
                $logLevel = $this->configManager
                    ->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY));
                $logLevel = Logger::toMonologLevel($logLevel);
                $lifeTime = $endTimestamp - $curTimestamp;
            }
        }

        $this->loggerCache->save(self::CACHE_KEY, $logLevel, $lifeTime);

        return $logLevel;
    }

    public function reset()
    {
        $this->logLevel = null;
    }
}
