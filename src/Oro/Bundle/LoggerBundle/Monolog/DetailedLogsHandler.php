<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Doctrine\Common\Cache\CacheProvider;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Writes logs using the logging level is stored in user configuration.
 * Also provides a possibility to change the logging level for a certain amount of time.
 */
class DetailedLogsHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    /** @var HandlerInterface */
    protected $handler;

    /** @var array */
    protected $buffer = [];

    /** @var ContainerInterface */
    protected $container;

    /** @var bool|null */
    private $loading;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param HandlerInterface $handler
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        $this->setLevel($this->getLogLevel());

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
     * @return string
     */
    private function getLogLevel()
    {
        /** @var CacheProvider $cache */
        $cache = $this->container->get('oro_logger.cache');

        $logLevel = $cache->fetch(Configuration::LOGS_LEVEL_KEY);
        if (false === $logLevel) {
            $logLevel = $this->container->getParameter('oro_logger.detailed_logs_default_level');
            if (!$this->loading) {
                $this->loading = true;
                try {
                    $logLevel = $this->loadLogLevel($cache, $logLevel);
                } finally {
                    $this->loading = false;
                }
            }
        }

        return $logLevel;
    }

    /**
     * @param CacheProvider $cache
     * @param string        $defaultLogLevel
     *
     * @return string
     */
    private function loadLogLevel(CacheProvider $cache, $defaultLogLevel)
    {
        $logLevel = $defaultLogLevel;
        $lifeTime = 0;
        if ($this->isInstalled() && $this->container->has('oro_config.user')) {
            /** @var ConfigManager $config */
            $config = $this->container->get('oro_config.user');

            $curTimestamp = time();
            $endTimestamp = $config->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY));
            if (null !== $endTimestamp && $curTimestamp <= $endTimestamp) {
                $logLevel = $config->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY));
                $lifeTime = $endTimestamp - $curTimestamp;
            }
        }

        $cache->save(Configuration::LOGS_LEVEL_KEY, $logLevel, $lifeTime);

        return $logLevel;
    }

    /**
     * @return bool
     */
    private function isInstalled()
    {
        return $this->container->hasParameter('installed') && $this->container->getParameter('installed');
    }
}
