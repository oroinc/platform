<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Doctrine\Common\Cache\CacheProvider;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;

/**
 * Writes logs using the logging level is stored in user configuration.
 * Also provides a possibility to change the logging level for a certain amount of time.
 */
class DetailedLogsHandler extends AbstractProcessingHandler
{
    /** @var HandlerInterface */
    protected $handler;

    /** @var array */
    protected $buffer = [];

    /** @var string|int */
    protected $detailedLogsDefaultLevel;

    /** @var bool|null */
    private $loading;

    /** @var ConfigManager|null */
    private $configManager;

    /** @var CacheProvider */
    private $loggerCache;

    /** @var string|null */
    private $installed;

    private ?ActivationStrategyInterface $activationStrategy = null;

    /**
     * @param ConfigManager|null $configManager
     * @param CacheProvider $loggerCache
     * @param string|null $installed
     * @param string|int $detailedLogsDefaultLevel
     * @param bool $bubble
     */
    public function __construct(
        ?ConfigManager $configManager,
        CacheProvider $loggerCache,
        ?string $installed,
        $detailedLogsDefaultLevel = Logger::ERROR,
        bool $bubble = true
    ) {
        parent::__construct($detailedLogsDefaultLevel, $bubble);

        $this->configManager = $configManager;
        $this->loggerCache = $loggerCache;
        $this->detailedLogsDefaultLevel = $detailedLogsDefaultLevel;
        $this->installed = $installed;
    }

    /**
     * @param HandlerInterface $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    public function setActivationStrategy(ActivationStrategyInterface $activationStrategy): void
    {
        $this->activationStrategy = $activationStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        $logLevel = $this->getLogLevel();
        if ($logLevel === $this->detailedLogsDefaultLevel &&
            $this->activationStrategy && !$this->activationStrategy->isHandlerActivated($record)) {
            return false;
        }
        $this->setLevel($logLevel);

        return parent::isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        if (!$this->handler) {
            throw new \LogicException(
                \sprintf(
                    "Trying to execute method `%s` which requires Handler to be set.",
                    __METHOD__
                )
            );
        }

        $this->handler->handle($record);
    }

    /**
     * @return string|int
     */
    private function getLogLevel()
    {
        $logLevel = $this->loggerCache->fetch(Configuration::LOGS_LEVEL_KEY);
        if (false === $logLevel) {
            $logLevel = $this->detailedLogsDefaultLevel;
            if (!$this->loading) {
                $this->loading = true;
                try {
                    $logLevel = $this->loadLogLevel($logLevel);
                } finally {
                    $this->loading = false;
                }
            }
        }

        return $logLevel ?? $this->level;
    }

    /**
     * @param string $defaultLogLevel
     *
     * @return string
     */
    private function loadLogLevel($defaultLogLevel)
    {
        $logLevel = $defaultLogLevel;
        $lifeTime = 0;
        if ($this->configManager && $this->isInstalled()) {
            $curTimestamp = time();
            $endTimestamp = $this->configManager
                ->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY));
            if (null !== $endTimestamp && $curTimestamp <= $endTimestamp) {
                $logLevel = $this->configManager
                    ->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY));
                $lifeTime = $endTimestamp - $curTimestamp;
            }
        }

        $this->loggerCache->save(Configuration::LOGS_LEVEL_KEY, $logLevel, $lifeTime);

        return $logLevel;
    }

    /**
     * @return bool
     */
    private function isInstalled()
    {
        return (bool)$this->installed;
    }
}
