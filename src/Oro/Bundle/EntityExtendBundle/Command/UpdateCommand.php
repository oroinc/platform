<?php

namespace Oro\Bundle\EntityExtendBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;

use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

class UpdateCommand extends InitCommand
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var bool verbosity flag
     */
    protected $info;

    /**
     * @var bool
     */
    protected $needDbUpdate = false;

    /**
     * Console command configuration
     */
    public function configure()
    {
        $this
            ->setName('oro:entity-extend:update')
            ->setDescription('Update custom(extended) entities and fields')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite config\'s option values')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Bundle filter(regExp), for example: \'Oro\\\\Bundle\\\\User*\', \'^Oro\\\\(.*)\\\\Region$\''
            );
    }

    /**
     * Runs command
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @throws \InvalidArgumentException
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->info = $output->getVerbosity() > 1 ? true : false;
        $force      = $input->getOption('force');
        $filter     = $input->getOption('filter');

        $output->writeln($this->getDescription());

        $this->configManager = $this->getContainer()->get('oro_entity_config.config_manager');

        /** @var Kernel $kernel */
        $kernel = $this->getContainer()->get('kernel');

        /** @var BundleInterface[] $bundles */
        $bundles = $this->getBundles($kernel, $filter);

        foreach ($bundles as $bundle) {
            $path = $bundle->getPath() . '/Resources/config/entity_extend.yml';
            if (is_file($path)) {
                $config = Yaml::parse(realpath($path));

                if ($this->info) {
                    $output->writeln('<info>Check bundle: ' . $bundle->getNamespace() . '</info>');
                }

                foreach ($config as $className => $entityOptions) {
                    $className = class_exists($className) ? $className : 'Extend\\Entity\\' . $className;
                    if ($this->info) {
                        $output->writeln(
                            '-- entity: ' . $className . ' (<comment>'
                            . (($this->configManager->hasConfig($className) ? 'EXISTS' : 'NEW'))
                            . '</comment>)'
                        );
                    }

                    $this->parseEntity($output, $className, $entityOptions, $force);
                }

                $this->configManager->flush();
            }
        }

        $this->getContainer()->get('oro_entity_extend.tools.dumper')->clear();
        $this->configManager->clearConfigurableCache();

        if ($this->needDbUpdate) {
            $console = escapeshellarg($this->getPhp()) . ' ' . escapeshellarg($kernel->getRootDir() . '/console');
            $env     = $kernel->getEnvironment();
            $output->writeln('Updating schema...');
            $commands = [
                'update'       => new Process($console . ' oro:entity-extend:update-config --env ' . $env),
                'schemaUpdate' => new Process($console . ' doctrine:schema:update --force --env ' . $env),
                'searchIndex'  => new Process($console . ' oro:search:create-index --env ' . $env),
            ];

            // put system in maintenance mode
            $maintenance = $this->getContainer()->get('oro_platform.maintenance');
            $maintenance->on();
            register_shutdown_function(
                function ($mode) {
                    $mode->off();
                },
                $maintenance
            );

            foreach ($commands as $command) {
                /** @var $command Process */
                $command->run();
            }
        }

        $output->writeln('<info>DONE</info>');
    }

    /**
     * @param Kernel $kernel
     * @param string $filter
     * @return \Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    protected function getBundles(Kernel $kernel, $filter)
    {
        /** @var BundleInterface[] $bundles */
        $bundles = $kernel->getBundles();
        if ($filter) {
            $bundles = array_filter(
                $bundles,
                function (BundleInterface $bundle) use ($filter) {
                    return preg_match('/' . str_replace('\\', '\\\\', $filter) . '/', $bundle->getNamespace());
                }
            );
        }

        return $bundles;
    }

    /**
     * @param OutputInterface $output
     * @param string $className       Entity's class name
     * @param array $entityOptions    Entity's options
     * @param bool $force             Flag to update existing entity model
     */
    protected function parseEntity($output, $className, $entityOptions, $force)
    {
        /** @var ExtendManager $extendManager */
        $extendManager  = $this->getContainer()->get('oro_entity_extend.extend.extend_manager');
        $configProvider = $extendManager->getConfigProvider();

        if (class_exists($className)) {
            $this->checkExtend($className);
        }

        if (!$this->configManager->hasConfig($className)) {
            $this->needDbUpdate = true;
            /**
             * create NEW entity model
             */
            $this->createEntityModel($className, $entityOptions);
            $this->setDefaultConfig($entityOptions, $className);

            $entityConfig = $configProvider->getConfig($className);
            $entityConfig->set(
                'owner',
                isset($entityOptions['owner']) ? $entityOptions['owner'] : ExtendManager::OWNER_SYSTEM
            );
            $entityConfig->set(
                'is_extend',
                isset($entityOptions['is_extend']) ? $entityOptions['is_extend'] : false
            );
        } elseif ($force) {
            /**
             * update EXISTING entity model on --force
             */
            $this->setDefaultConfig($entityOptions, $className);
        }

        foreach ($entityOptions['fields'] as $fieldName => $fieldConfig) {
            $fieldExistingConfig = $this->configManager->hasConfig($className, $fieldName);

            if ($this->info) {
                $output->writeln(
                    '-- -- field: <info>' . $fieldName . '</info> (<comment>'
                    . ((bool)$fieldExistingConfig ? 'EXISTS' : 'NEW')
                    . '</comment>)'
                );
            }

            list($mode, $owner, $isExtend) = $this->parseFieldConfig($fieldConfig);

            $config = false;
            if (! (bool) $fieldExistingConfig) {
                /**
                 * create NEW entity field model
                 */
                $extendManager->createField($className, $fieldName, $fieldConfig, $owner, $mode);
                $this->setDefaultConfig($entityOptions, $className, $fieldName);

                $config = $configProvider->getConfig($className, $fieldName);
                $config->set('state', ExtendManager::STATE_NEW);
                $config->set('is_extend', $isExtend);

                $this->needDbUpdate = true;
            } elseif ($force) {
                /**
                 * update EXISTING entity field model on --force
                 */
                $this->setDefaultConfig($entityOptions, $className, $fieldName);

                $config = $configProvider->getConfig($className, $fieldName);
            }

            if ($config) {
                $this->configManager->persist($config);
            }
        }
    }

    /**
     * @param array $fieldConfig
     * @return array
     */
    protected function parseFieldConfig($fieldConfig)
    {
        return [
            isset($fieldConfig['mode']) ? $fieldConfig['mode'] : ConfigModelManager::MODE_DEFAULT,
            isset($fieldConfig['owner']) ? $fieldConfig['owner'] : ExtendManager::OWNER_SYSTEM,
            isset($fieldConfig['is_extend']) ? $fieldConfig['is_extend'] : false,
        ];
    }

    /**
     * @return string|\Symfony\Component\Process\false
     * @throws \RuntimeException
     */
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
