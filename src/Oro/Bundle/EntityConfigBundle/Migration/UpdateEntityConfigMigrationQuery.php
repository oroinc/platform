<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigDumper;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class UpdateEntityConfigMigrationQuery implements MigrationQuery
{
    /**
     * @var ConfigDumper
     */
    protected $configDumper;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(ConfigDumper $configDumper, KernelInterface $kernel)
    {
        $this->configDumper = $configDumper;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'UPDATE ENTITY CONFIG';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Connection $connection)
    {
        $console = escapeshellarg($this->getPhp()) . ' ' . escapeshellarg($this->kernel->getRootDir() . '/console');
        $env     = $this->kernel->getEnvironment();
        $process = new Process($console . ' oro:entity-config:update --env ' . $env);
        $output = '';
        $process->run(
            function ($type, $data) use (&$output) {
                $output .= $data;
            }
        );
        var_dump($output);

        //$this->configDumper->updateConfigs();
    }

    protected function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException(
                'The php executable could not be found, add it to your PATH environment variable and try again'
            );
        }

        return $phpPath;
    }
}
