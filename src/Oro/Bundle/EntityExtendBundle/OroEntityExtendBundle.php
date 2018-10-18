<?php

namespace Oro\Bundle\EntityExtendBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ConfigLoaderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityExtendPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityManagerPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityMetadataBuilderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ExtensionPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\MigrationConfigPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\WarmerPass;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Warms up extended entity cache during boot in a case when the cache is empty
 */
class OroEntityExtendBundle extends Bundle
{
    /**
     * We have to use infinite timeout because this command is executed in background
     * and we do not have a way to manage this timeout from a caller command.
     * As result it is possible that parent command will fail if this command is interrupted
     * by the timeout.
     * E.g. this can occur when oro:install executes cache:clear.
     */
    const CACHE_GENERATION_TIMEOUT = null;
    const CACHE_CHECKOUT_INTERVAL = 1;
    const CACHE_CHECKOUT_ATTEMPTS = 120;

    /** @var KernelInterface */
    private $kernel;

    /** @var string */
    private $cacheDir;

    /** @var string */
    private $phpExecutable;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel   = $kernel;
        $this->cacheDir = $kernel->getCacheDir();

        ExtendClassLoadingUtils::registerClassLoader($this->cacheDir);
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
                [
                    ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) =>
                        ExtendClassLoadingUtils::getEntityNamespace()
                ]
            )
        );
        $container->addCompilerPass(new ExtensionPass());
        $container->addCompilerPass(new WarmerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    private function ensureInitialized()
    {
        ExtendClassLoadingUtils::ensureDirExists(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));
        if (!CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            && !CommandExecutor::isCurrentCommand('oro:install')
            && !CommandExecutor::isCurrentCommand('oro:platform:upgrade20')
        ) {
            if (!ExtendClassLoadingUtils::aliasesExist($this->cacheDir)
                && !CommandExecutor::isCommandRunning('oro:entity-extend:update-config')
            ) {
                $this->checkConfigs();
                $this->initializeCache();
            }
            $this->ensureAliasesSet();
        }
    }

    private function checkConfigs()
    {
        // We have to check the extend entity configs in separate process to prevent conflicts
        // with 'class_alias' function is used in 'oro:entity-extend:cache:warmup' command.
        $pb = $this->createProcessBuilder('oro:entity-extend:cache:check');

        $attempts = 0;
        do {
            if (!CommandExecutor::isCommandRunning('oro:entity-extend:cache:check')) {
                // if configs were checked there is no need to check them again
                if ($attempts > 0) {
                    return;
                }

                $process = $pb->getProcess();
                $exitStatusCode = $process->run();
                if ($exitStatusCode) {
                    $output = $process->getErrorOutput();

                    if (empty($output)) {
                        $output = $process->getOutput();
                    }
                    throw new \RuntimeException($output);
                }

                return;
            } else {
                $attempts++;
                sleep(self::CACHE_CHECKOUT_INTERVAL);
            }
        } while ($attempts < self::CACHE_CHECKOUT_ATTEMPTS);
    }

    private function initializeCache()
    {
        // We have to warm up the extend entities cache in separate process
        // to allow this process continue executing.
        // The problem is we need initialized DI contained for warming up this cache,
        // but in this moment we are exactly doing this for the current process.
        $pb = $this->createProcessBuilder('oro:entity-extend:cache:warmup');

        $attempts = 0;
        do {
            if (!CommandExecutor::isCommandRunning('oro:entity-extend:cache:warmup')) {
                // if cache was generated there is no need to generate it again
                if ($attempts > 0) {
                    return;
                }

                $process = $pb->getProcess();
                $exitStatusCode = $process->run();
                if ($exitStatusCode) {
                    throw new \RuntimeException($process->getErrorOutput());
                }

                return;
            } else {
                $attempts++;
                sleep(self::CACHE_CHECKOUT_INTERVAL);
            }
        } while ($attempts < self::CACHE_CHECKOUT_ATTEMPTS);
    }

    private function ensureAliasesSet()
    {
        if (!CommandExecutor::isCurrentCommand('oro:entity-extend:update-config')) {
            ExtendClassLoadingUtils::setAliases($this->cacheDir);
        }
    }

    /**
     * Finds the PHP executable.
     *
     * @return string
     */
    private function getPhpExecutable()
    {
        if (null === $this->phpExecutable) {
            $this->phpExecutable = CommandExecutor::getPhpExecutable();
        }

        return $this->phpExecutable;
    }

    /**
     * @param string $commandName
     *
     * @return ProcessBuilder
     */
    private function createProcessBuilder(string $commandName): ProcessBuilder
    {
        return ProcessBuilder::create()
            ->setTimeout(self::CACHE_GENERATION_TIMEOUT)
            ->add($this->getPhpExecutable())
            ->add($this->kernel->getProjectDir() . '/bin/console')
            ->add($commandName)
            ->add(sprintf('%s=%s', '--env', $this->kernel->getEnvironment()))
            ->add(sprintf('%s=%s', '--cache-dir', $this->cacheDir));
    }
}
