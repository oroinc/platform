<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Detects whether user-configurable log level is active and returns it as an integer
 */
class LogLevelConfig implements ResetInterface
{
    public const CACHE_KEY = 'configurable_monolog_level';

    private int $defaultLevel;

    /** @var bool tracks recursive calls of the log level loading */
    private bool $logLevelLoadingFlag = false;

    private CacheInterface $loggerCache;

    private ?ConfigManager $configManager;

    private ApplicationState $applicationState;

    private ?int $logLevel = null;

    public function __construct(
        CacheInterface $loggerCache,
        ?ConfigManager $configManager,
        ApplicationState $applicationState,
        $defaultLevel = Logger::ERROR
    ) {
        $this->configManager = $configManager;
        $this->loggerCache = $loggerCache;
        $this->defaultLevel = Logger::toMonologLevel($defaultLevel);
        $this->applicationState = $applicationState;
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

        $this->logLevel = $this->loggerCache->get(self::CACHE_KEY, function (ItemInterface $item) {
            $logLevel = $this->defaultLevel;
            if ($this->logLevelLoadingFlag) {
                return $logLevel;
            }
            $this->logLevelLoadingFlag = true;
            $lifeTime = 0;
            try {
                if ($this->configManager && $this->applicationState->isInstalled()) {
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
            } finally {
                $this->logLevelLoadingFlag = false;
            }
            $item->expiresAfter($lifeTime);

            return $logLevel;
        });

        return $this->logLevel;
    }

    public function reset()
    {
        $this->logLevel = null;
    }
}
