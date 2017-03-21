<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Doctrine\Common\Cache\CacheProvider;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;

class DetailedLogsHandler extends AbstractProcessingHandler implements ContainerAwareInterface
{
    /** @var HandlerInterface */
    protected $handler;

    /** @var array */
    protected $buffer = [];

    /** @var ContainerInterface */
    protected $container;

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
        $this->handler->handle($record);
    }

    /**
     * @return int
     */
    private function getLogLevel()
    {
        /** @var CacheProvider $cache */
        $cache = $this->container->get('oro_logger.cache');
        if ($cache->contains(Configuration::LOGS_LEVEL_KEY)) {
            return $cache->fetch(Configuration::LOGS_LEVEL_KEY);
        }

        $logLevel = $this->container->getParameter('oro_logger.detailed_logs_default_level');
        if ($this->isInstalled() && $this->container->has('oro_config.user')) {
            /** @var ConfigManager $config */
            $config = $this->container->get('oro_config.user');

            $curTimestamp = time();
            $endTimestamp = $config->get(Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY));
            if (null !== $endTimestamp && $curTimestamp <= $endTimestamp) {
                $logLevel = $config->get(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY));

                $cache->save(Configuration::LOGS_LEVEL_KEY, $logLevel, $endTimestamp - $curTimestamp);

                return $logLevel;
            }
        }

        $cache->save(Configuration::LOGS_LEVEL_KEY, $logLevel);

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
