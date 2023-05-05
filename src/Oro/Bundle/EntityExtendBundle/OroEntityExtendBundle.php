<?php

namespace Oro\Bundle\EntityExtendBundle;

use Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;
use Oro\Bundle\EntityExtendBundle\EntityExtend\ExtendedEntityFieldsProcessor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Process;

class OroEntityExtendBundle extends Bundle
{
    /**
     * We have to use infinite timeout because this command is executed in background
     * and we do not have a way to manage this timeout from a caller command.
     * As result it is possible that parent command will fail if this command is interrupted
     * by the timeout.
     * E.g. this can occur when oro:install executes cache:clear.
     */
    private const CACHE_GENERATION_TIMEOUT = null;
    private const CACHE_CHECKOUT_INTERVAL = 1;
    private const CACHE_CHECKOUT_ATTEMPTS = 120;

    private KernelInterface $kernel;
    private string $cacheDir;
    private ?string $phpExecutable = null;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->cacheDir = $kernel->getCacheDir();

        ExtendClassLoadingUtils::registerClassLoader($this->cacheDir);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        parent::boot();

        $this->ensureInitialized();

        $entityFieldIterator = $this->container->get('oro_entity_extend.entity_field_iterator');
        $metadataProvider = $this->container->get('oro_entity_extend.entity_metadata_provider');
        ExtendedEntityFieldsProcessor::initialize($entityFieldIterator, $metadataProvider);

        if (!class_exists('Symfony\Component\Validator\Mapping\PropertyMetadata', false)) {
            class_alias(
                'Oro\Bundle\EntityExtendBundle\Validator\Mapping\PropertyMetadata',
                'Symfony\Component\Validator\Mapping\PropertyMetadata'
            );
        }
        if (!class_exists('Symfony\Component\Validator\Mapping\GetterMetadata', false)) {
            class_alias(
                'Oro\Bundle\EntityExtendBundle\Validator\Mapping\GetterMetadata',
                'Symfony\Component\Validator\Mapping\GetterMetadata'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $this->ensureInitialized();

        $container->addCompilerPass(new Compiler\EntityExtendValidationLoaderPass());
        $container->addCompilerPass(new Compiler\ConfigLoaderPass());
        $container->addCompilerPass(new Compiler\EntityManagerPass());
        $container->addCompilerPass(new Compiler\MigrationConfigPass());
        $container->addCompilerPass(new Compiler\ExtendDuplicatorPass());
        $container->addCompilerPass(new Compiler\ChangePropertyAccessorReflectionExtractorPass());
        $container->addCompilerPass(new Compiler\WarmerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    private function ensureInitialized()
    {
        // this needed on application update to run oro_entity_extend.warmer tagged services
        // instead of regular cache warmup
        ExtendClassLoadingUtils::ensureDirExists(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));
        if (!CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            && !CommandExecutor::isCurrentCommand('oro:install')
            && !CommandExecutor::isCurrentCommand('oro:assets:install')
        ) {
            if (!ExtendClassLoadingUtils::aliasesExist($this->cacheDir)
                && !CommandExecutor::isCommandRunning('oro:entity-extend:update-config')
            ) {
                $this->checkConfigs();
                $this->initializeCache();
            }
        }
    }

    private function checkConfigs()
    {
        // We have to check the extend entity configs in separate process to prevent conflicts
        // with 'class_alias' function is used in 'oro:entity-extend:cache:warmup' command.
        $attempts = 0;
        do {
            if (!CommandExecutor::isCommandRunning('oro:entity-extend:cache:check')) {
                // if configs were checked there is no need to check them again
                if ($attempts > 0) {
                    return;
                }

                $process = $this->getProcess('oro:entity-extend:cache:check');

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
        $attempts = 0;
        do {
            if (!CommandExecutor::isCommandRunning('oro:entity-extend:cache:warmup')) {
                // if cache was generated there is no need to generate it again
                if ($attempts > 0) {
                    return;
                }

                $process = $this->getProcess('oro:entity-extend:cache:warmup');

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

    /**
     * Finds the PHP executable.
     */
    private function getPhpExecutable(): string
    {
        if (null === $this->phpExecutable) {
            $this->phpExecutable = CommandExecutor::getPhpExecutable();
        }

        return $this->phpExecutable;
    }

    /**
     * Creates and returns Process instance with configured parameters and timeout set
     */
    private function getProcess(string $commandName): Process
    {
        $projectDir = $this->kernel->getProjectDir();
        $processArguments = [
            $this->getPhpExecutable(),
            $projectDir . '/bin/console',
            $commandName,
            sprintf('%s=%s', '--env', $this->kernel->getEnvironment()),
            sprintf('%s=%s', '--cache-dir', $this->cacheDir)
        ];

        $process = new Process($processArguments, $projectDir);
        $process->setTimeout(self::CACHE_GENERATION_TIMEOUT);

        return $process;
    }
}
