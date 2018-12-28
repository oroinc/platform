<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Psr\Log\LoggerInterface;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor as BaseCommandExecutor;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Symfony\Component\Process\ProcessBuilder;

class CommandExecutor extends BaseCommandExecutor
{
    /**
     * @var string
     */
    protected $consoleCmdPath;

    /**
     * @var string
     */
    protected $env;

    /**
     * @var OroDataCacheManager
     */
    protected $dataCacheManager;

    /**
     * @var array
     */
    protected $defaultOptions = ['process-timeout' => self::DEFAULT_TIMEOUT];

    /**
     * @var int
     *
     * @deprecated since 1.8. Use {@see getDefaultOption('process-timeout')} instead
     */
    protected $defaultTimeout = self::DEFAULT_TIMEOUT;

    /**
     * @param string $consoleCmdPath
     * @param string $env
     * @param OroDataCacheManager|null $dataCacheManager
     */
    public function __construct($consoleCmdPath, $env, OroDataCacheManager $dataCacheManager = null)
    {
        parent::__construct($consoleCmdPath, $env);

        $this->dataCacheManager = $dataCacheManager;
    }

    /**
     * @inheritdoc
     */
    public function runCommand($command, $params = [], LoggerInterface $logger = null)
    {
        $disableCacheSync = false;
        if (array_key_exists('--disable-cache-sync', $params)) {
            $disableCacheSync = $params['--disable-cache-sync'];
            unset($params['--disable-cache-sync']);
        }

        $exitCode = parent::runCommand($command, $params, $logger);
        if ($this->dataCacheManager && !$disableCacheSync) {
            $this->dataCacheManager->sync();
        }

        return $exitCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption($name)
    {
        return parent::getDefaultOption($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOption($name, $value = true)
    {
        return parent::setDefaultOption($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function processResult($exitCode, $ignoreErrors, LoggerInterface $logger)
    {
        parent::processResult($exitCode, $ignoreErrors, $logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareParameters($command, array $params)
    {
        return parent::prepareParameters($command, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function processParameter(ProcessBuilder $pb, $name, $value)
    {
        parent::processParameter($pb, $name, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function addParameter(ProcessBuilder $pb, $name, $value = null)
    {
        parent::addParameter($pb, $name, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPhp()
    {
        return parent::getPhp();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTimeout()
    {
        return parent::getDefaultTimeout();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        return parent::setDefaultTimeout($defaultTimeout);
    }
}
