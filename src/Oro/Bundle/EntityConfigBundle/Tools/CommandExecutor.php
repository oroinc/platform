<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutorInterface;
use Psr\Log\LoggerInterface;

/**
 * The class that contains a set of methods to simplify execution of console commands in a separate process.
 */
class CommandExecutor implements CommandExecutorInterface
{
    /**
     * @var CommandExecutorInterface
     */
    private $commandExecutor;

    /**
     * @var OroDataCacheManager
     */
    private $dataCacheManager;

    /**
     * @param CommandExecutorInterface $commandExecutor
     * @param OroDataCacheManager|null $dataCacheManager
     */
    public function __construct(CommandExecutorInterface $commandExecutor, OroDataCacheManager $dataCacheManager = null)
    {
        $this->commandExecutor = $commandExecutor;
        $this->dataCacheManager = $dataCacheManager;
    }

    /**
     * @inheritdoc
     */
    public function runCommand(string $command, array $params = [], LoggerInterface $logger = null): int
    {
        $disableCacheSync = false;
        if (array_key_exists('--disable-cache-sync', $params)) {
            $disableCacheSync = $params['--disable-cache-sync'];
            unset($params['--disable-cache-sync']);
        }

        $exitCode = $this->commandExecutor->runCommand($command, $params, $logger);
        if (!$disableCacheSync) {
            $this->dataCacheManager->sync();
        }

        return $exitCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(string $name)
    {
        return $this->commandExecutor->getDefaultOption($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOption(string $name, $value = true): CommandExecutorInterface
    {
        $this->commandExecutor->setDefaultOption($name, $value);

        return $this;
    }
}
