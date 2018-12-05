<?php

namespace Oro\Bundle\RequireJSBundle\Command;

use Oro\Bundle\AssetBundle\NodeProcessFactory;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Build RequireJs assets
 */
class OroBuildCommand extends Command
{
    protected const BUILD_CONFIG_FILE_NAME    = 'build.js';
    protected const OPTIMIZER_FILE_PATH       = 'bundles/npmassets/requirejs/bin/r.js';

    /**
     * @var NodeProcessFactory
     */
    private $nodeProcessFactory;

    /**
     * @var ConfigProviderManager
     */
    private $configProviderManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var null|float|int
     */
    private $timeout;

    /**
     * @var string
     */
    private $webRoot;

    /**
     * @param NodeProcessFactory    $nodeProcessFactory
     * @param ConfigProviderManager $configProviderManager
     * @param Filesystem            $filesystem
     * @param string                $webRoot
     * @param int|float|null        $timeout
     */
    public function __construct(
        NodeProcessFactory $nodeProcessFactory,
        ConfigProviderManager $configProviderManager,
        Filesystem $filesystem,
        string $webRoot,
        $timeout
    ) {
        $this->nodeProcessFactory = $nodeProcessFactory;
        $this->configProviderManager = $configProviderManager;
        $this->filesystem = $filesystem;
        $this->webRoot = $webRoot;
        $this->timeout = $timeout;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:requirejs:build')
            ->setDescription('Build single optimized js resource');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->configProviderManager->getProviders() as $provider) {
            foreach ($provider->collectConfigs() as $config) {
                $output->writeln('Generating require.js config');

                $configPath = $this->webRoot . self::BUILD_CONFIG_FILE_NAME;

                // for some reason built application gets broken with configuration in "oneline-json"
                $mainConfig = str_replace(',', ",\n", $config->getMainConfig());
                $this->writeFile($mainConfig, $this->webRoot . $config->getConfigFilePath());

                $buildConfig = '(' . json_encode($config->getBuildConfig()) . ')';
                $this->writeFile($buildConfig, $configPath);

                $output->writeln('Running code optimizer');

                $this->process($configPath);

                $output->writeln('Cleaning up');

                $this->removeFile($configPath);

                $output->writeln(
                    sprintf(
                        '<comment>%s</comment> <info>[file+]</info> %s',
                        date('H:i:s'),
                        realpath($this->webRoot . $config->getOutputFilePath())
                    )
                );
            }
        }
    }

    /**
     * @param string $configPath
     */
    protected function process(string $configPath): void
    {
        $path = dirname($configPath) . DIRECTORY_SEPARATOR . basename($configPath);
        $command = self::OPTIMIZER_FILE_PATH . ' -o ' . $path . ' 1>&2';

        $process = $this->nodeProcessFactory->createProcess($command, $this->webRoot, $this->timeout);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * Write config to config file
     *
     * @param string $content
     * @param string $path
     *
     * @return OroBuildCommand
     */
    protected function writeFile($content, $path)
    {
        try {
            $this->filesystem->dumpFile($path, $content);
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException('Unable to write file ' . $e->getPath());
        }

        return $this;
    }

    /**
     * Write config to config file
     *
     * @param string $path
     *
     * @return OroBuildCommand
     */
    protected function removeFile($path)
    {
        try {
            $this->filesystem->remove($path);
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException('Unable to remove file ' . $e->getPath());
        }

        return $this;
    }
}
