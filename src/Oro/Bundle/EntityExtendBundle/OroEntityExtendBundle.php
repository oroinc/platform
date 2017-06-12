<?php

namespace Oro\Bundle\EntityExtendBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\ProcessBuilder;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;

use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ConfigLoaderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityExtendPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityManagerPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\EntityMetadataBuilderPass;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\MigrationConfigPass;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler\ExtensionPass;
use Oro\Bundle\InstallerBundle\CommandExecutor;

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
                    ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) => 'Extend\Entity'
                ]
            )
        );
        $container->addCompilerPass(new ExtensionPass());
    }

    private function ensureInitialized()
    {
        if (!CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)) {
            ExtendClassLoadingUtils::ensureDirExists(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));
            if (!file_exists(ExtendClassLoadingUtils::getAliasesPath($this->cacheDir))) {
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
        $pb = ProcessBuilder::create()
            ->setTimeout(self::CACHE_GENERATION_TIMEOUT)
            ->add($this->getPhpExecutable())
            ->add($this->kernel->getRootDir() . '/console')
            ->add('oro:entity-extend:cache:check')
            ->add('--env')
            ->add($this->kernel->getEnvironment())
            ->add('--cache-dir')
            ->add($this->cacheDir);

        $attempts = 0;
        do {
            if (!CommandExecutor::isCommandRunning('oro:entity-extend:cache:check')) {
                // if configs were checked there is no need to check them again
                if ($attempts > 0) {
                    return;
                }

                $pb->getProcess()->run();

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
        $pb = ProcessBuilder::create()
            ->setTimeout(self::CACHE_GENERATION_TIMEOUT)
            ->add($this->getPhpExecutable())
            ->add($this->kernel->getRootDir() . '/console')
            ->add('oro:entity-extend:cache:warmup')
            ->add('--env')
            ->add($this->kernel->getEnvironment())
            ->add('--cache-dir')
            ->add($this->cacheDir);

        $attempts = 0;
        do {
            if (!CommandExecutor::isCommandRunning('oro:entity-extend:cache:warmup')) {
                // if cache was generated there is no need to generate it again
                if ($attempts > 0) {
                    return;
                }

                $pb->getProcess()->run();

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
}
