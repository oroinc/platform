<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class RefreshExtendConfigMigrationQuery implements MigrationQuery
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var string */
    protected $initialEntityConfigState;

    /** @var string */
    protected $initialEntityConfigStatePath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param array           $initialEntityConfigState
     * @param string          $initialEntityConfigStatePath
     */
    public function __construct(
        CommandExecutor $commandExecutor,
        $initialEntityConfigState,
        $initialEntityConfigStatePath
    ) {
        $this->commandExecutor              = $commandExecutor;
        $this->initialEntityConfigState     = $initialEntityConfigState;
        $this->initialEntityConfigStatePath = $initialEntityConfigStatePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Refresh extend entity configs';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        if (empty($this->initialEntityConfigState)) {
            $this->commandExecutor->runCommand(
                'oro:entity-extend:update-config',
                [],
                $logger
            );
        } else {
            $this->createInitialEntityConfigStateFile();
            try {
                $this->commandExecutor->runCommand(
                    'oro:entity-extend:update-config',
                    ['--initial-state-path' => $this->initialEntityConfigStatePath],
                    $logger
                );
                $this->removeInitialEntityConfigStateFile();
            } catch (\Exception $ex) {
                $this->removeInitialEntityConfigStateFile();
                throw $ex;
            }
        }
    }

    /**
     * Saves an options in a cache
     */
    protected function createInitialEntityConfigStateFile()
    {
        $this->removeInitialEntityConfigStateFile();
        $this->ensureDirExists(dirname($this->initialEntityConfigStatePath));
        file_put_contents($this->initialEntityConfigStatePath, serialize($this->initialEntityConfigState));
    }

    /**
     * Removes options file from a cache
     */
    protected function removeInitialEntityConfigStateFile()
    {
        if (is_file($this->initialEntityConfigStatePath)) {
            unlink($this->initialEntityConfigStatePath);
        }
    }

    /**
     * Checks if directory exists and attempts to create it if it does not exist.
     *
     * @param string $dir
     *
     * @throws \RuntimeException
     */
    protected function ensureDirExists($dir)
    {
        if (!is_dir($dir) && false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException(sprintf('Could not create directory "%s".', $dir));
        }
    }
}
