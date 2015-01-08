<?php

namespace Oro\Bundle\EntityExtendBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ConfigLoaderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityExtendPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityManagerPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityMetadataBuilderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\MigrationConfigPass;
use Oro\Bundle\EntityExtendBundle\Exception\RuntimeException;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ExtensionPass;
use Oro\Bundle\EntityExtendBundle\Command\CacheWarmupCommand;
use Oro\Bundle\EntityExtendBundle\Command\UpdateConfigCommand;
use Oro\Bundle\InstallerBundle\Process\PhpExecutableFinder;
use Oro\Bundle\InstallerBundle\CommandExecutor;

class OroEntityExtendBundle extends Bundle
{
    const DOCTRINE_CLEAR_COMMAND   = 'doctrine:cache:clear-metadata';
    const CACHE_GENERATION_TIMEOUT = 300;
    const CACHE_CHECKOUT_INTERVAL  = 1;
    const CACHE_CHECKOUT_ATTEMPTS  = 120;

    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        ExtendClassLoadingUtils::registerClassLoader($this->kernel->getCacheDir());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->ensureInitialized();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $this->ensureInitialized();

        $container->addCompilerPass(new EntityExtendPass());
        $container->addCompilerPass(new ConfigLoaderPass());
        $container->addCompilerPass(new EntityManagerPass());
        $container->addCompilerPass(new EntityMetadataBuilderPass());
        $container->addCompilerPass(new MigrationConfigPass());
        $container->addCompilerPass(
            DoctrineOrmMappingsPass::createYamlMappingDriver(
                array(
                    ExtendClassLoadingUtils::getEntityCacheDir($this->kernel->getCacheDir()) => 'Extend\Entity'
                )
            )
        );
        $container->addCompilerPass(new ExtensionPass());
    }

    private function ensureInitialized()
    {
        $this->ensureDirExists(ExtendClassLoadingUtils::getEntityCacheDir($this->kernel->getCacheDir()));
        $this->ensureCacheInitialized();
        $this->ensureAliasesSet();
    }

    private function ensureCacheInitialized()
    {
        $aliasesPath = ExtendClassLoadingUtils::getAliasesPath($this->kernel->getCacheDir());
        if (file_exists($aliasesPath)
            || $this->isCurrentCommand(CacheWarmupCommand::NAME)
            || $this->isCurrentCommand(self::DOCTRINE_CLEAR_COMMAND)
        ) {
            return;
        }

        $attempts = 0;
        do {
            if (!$this->isCommandRunning(CacheWarmupCommand::NAME)
                && !$this->isCommandRunning(self::DOCTRINE_CLEAR_COMMAND)
            ) {
                // if cache was generated there is no need to generate it again
                if ($attempts > 0) {
                    return;
                }

                $console = escapeshellarg($this->getPhp()) . ' '
                    . escapeshellarg($this->kernel->getRootDir() . '/console');
                $env = $this->kernel->getEnvironment();

                // We have to warm up the extend entities cache in separate process
                // to allow this process continue executing.
                // The problem is we need initialized DI contained for warming up this cache,
                // but in this moment we are exactly doing this for the current process.
                $process = new Process($console . ' ' . CacheWarmupCommand::NAME . ' --env ' . $env);
                $process->setTimeout(self::CACHE_GENERATION_TIMEOUT);
                $process->run();

                // Doctrine metadata might be invalid after extended cache generation
                $process = new Process($console . ' ' . self::DOCTRINE_CLEAR_COMMAND . ' --env ' . $env);
                $process->setTimeout(self::CACHE_GENERATION_TIMEOUT);
                $process->run();

                return;
            } else {
                $attempts++;
                sleep(self::CACHE_CHECKOUT_INTERVAL);
            }
        } while ($attempts < self::CACHE_CHECKOUT_ATTEMPTS);
    }

    private function ensureAliasesSet()
    {
        if (!$this->isCurrentCommand(UpdateConfigCommand::NAME)) {
            ExtendClassLoadingUtils::setAliases($this->kernel->getCacheDir());
        }
    }

    private function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException(
                'The php executable could not be found, add it to your PATH environment variable and try again'
            );
        }

        return $phpPath;
    }

    /**
     * Checks if directory exists and attempts to create it if it doesn't exist.
     *
     * @param string $dir
     * @throws RuntimeException
     */
    private function ensureDirExists($dir)
    {
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new RuntimeException(sprintf('Could not create cache directory "%s".', $dir));
            }
        }
    }

    /**
     * Indicates if the given command is being executed
     *
     * @param string $commandName
     * @return bool
     */
    private function isCommandRunning($commandName)
    {
        return CommandExecutor::isCommandRunning($commandName);
    }

    /**
     * Check if this process executes specified command
     *
     * @param string $commandName
     * @return bool
     */
    private function isCurrentCommand($commandName)
    {
        return CommandExecutor::isCurrentCommand($commandName);
    }
}
