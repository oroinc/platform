<?php

namespace Oro\Bundle\LoggerBundle\Monolog;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class DetailedLogsHandler extends AbstractHandler implements ContainerAwareInterface
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
        $this->level = $this->getLogLevel();
        return parent::isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        $this->buffer[] = $record;

        return false === $this->bubble;
    }

    public function close()
    {
        if (count($this->buffer) === 0) {
            return;
        }

        $level = $this->level;

        $this->handler->handleBatch(
            array_filter(
                $this->buffer,
                function ($record) use ($level) {
                    return $record['level'] >= $level;
                }
            )
        );
    }

    /**
     * @return int
     */
    private function getLogLevel()
    {
        $logLevel = $this->container->getParameter('oro_logger.detailed_logs_default_level');
        if ($this->isInstalled() && $this->container->has('oro_config.user')) {
            /** @var ConfigManager $config */
            $config = $this->container->get('oro_config.user');
            $endTimestamp = $config->get('oro_logger.detailed_logs_end_timestamp');
            if (null !== $endTimestamp && time() <= $endTimestamp) {
                $logLevel = $config->get('oro_logger.detailed_logs_level');
            }
        }

        return Logger::toMonologLevel($logLevel);
    }

    /**
     * @return bool
     */
    private function isInstalled()
    {
        return $this->container->hasParameter('installed') && $this->container->getParameter('installed');
    }
}
