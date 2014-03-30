<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class UpdateExtendConfigMigrationQuery implements MigrationQuery
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @var string
     */
    protected $configProcessorOptionsPath;

    /**
     * @param array           $options
     * @param CommandExecutor $commandExecutor
     * @param string          $configProcessorOptionsPath
     */
    public function __construct(
        array $options,
        CommandExecutor $commandExecutor,
        $configProcessorOptionsPath
    ) {
        $this->options                    = $options;
        $this->commandExecutor            = $commandExecutor;
        $this->configProcessorOptionsPath = $configProcessorOptionsPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->runUpdateConfigCommand($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->runUpdateConfigCommand($logger);
    }

    /**
     * Executes oro:entity-extend:migration:update-config command
     *
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     * @throws \Exception
     */
    protected function runUpdateConfigCommand(LoggerInterface $logger, $dryRun = false)
    {
        $this->createConfigProcessorOptionsFile();
        try {
            $params = [];
            if ($dryRun) {
                $params['--dry-run']       = true;
                $params['--ignore-errors'] = true;
            }
            $this->commandExecutor->runCommand(
                'oro:entity-extend:migration:update-config',
                $params,
                $logger
            );
            $this->removeConfigProcessorOptionsFile();
        } catch (\Exception $ex) {
            $this->removeConfigProcessorOptionsFile();
            throw $ex;
        }
    }

    /**
     * Saves an options in a cache
     */
    protected function createConfigProcessorOptionsFile()
    {
        $this->removeConfigProcessorOptionsFile();
        $this->ensureDirExists(dirname($this->configProcessorOptionsPath));
        file_put_contents($this->configProcessorOptionsPath, Yaml::dump($this->options));
    }

    /**
     * Removes options file from a cache
     */
    protected function removeConfigProcessorOptionsFile()
    {
        if (is_file($this->configProcessorOptionsPath)) {
            unlink($this->configProcessorOptionsPath);
        }
    }

    /**
     * Checks if directory exists and attempts to create it if it does not exist.
     *
     * @param string $dir
     * @throws \RuntimeException
     */
    protected function ensureDirExists($dir)
    {
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf('Could not create directory "%s".', $dir));
            }
        }
    }
}
