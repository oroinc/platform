<?php

namespace Oro\Bundle\RequireJSBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\ConfigProviderCompilerPass;
use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;

class OroBuildCommand extends ContainerAwareCommand
{
    const BUILD_CONFIG_FILE_NAME    = 'build.js';
    const OPTIMIZER_FILE_PATH       = 'bundles/ororequirejs/lib/r.js';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->filesystem = new Filesystem();
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
        $this->config = $this->getContainer()->getParameter('oro_require_js');
        
        /** @var ConfigProviderManager $manager */
        $manager = $this->getContainer()->get(ConfigProviderCompilerPass::PROVIDER_SERVICE);
        foreach ($manager->getProviders() as $provider) {
            foreach ($provider->collectConfigs() as $config) {
                $output->writeln(sprintf('Generating require.js config'));

                $configPath = $this->getWebRoot() . self::BUILD_CONFIG_FILE_NAME;

                // for some reason built application gets broken with configuration in "oneline-json"
                $mainConfig = str_replace(',', ",\n", $config->getMainConfig());
                $this->writeFile($mainConfig, $this->getWebRoot() . $config->getConfigFilePath());

                $buildConfig = '(' . json_encode($config->getBuildConfig()) . ')';
                $this->writeFile($buildConfig, $configPath);

                $JSEngine = isset($this->config['js_engine']) ? $this->config['js_engine'] : null;

                if (!$JSEngine) {
                    throw new \RuntimeException("JS engine not found");
                }

                $output->writeln(sprintf('Running code optimizer'));

                $this->process($JSEngine, $configPath);

                $output->writeln('Cleaning up');

                $this->removeFile($configPath);

                $output->writeln(
                    sprintf(
                        '<comment>%s</comment> <info>[file+]</info> %s',
                        date('H:i:s'),
                        realpath($this->getWebRoot() . $config->getOutputFilePath())
                    )
                );
            }
        }
    }

    /**
     * @param string $JSEngine
     * @param string $configPath
     */
    protected function process($JSEngine, $configPath)
    {
        $path = dirname($configPath) . DIRECTORY_SEPARATOR . basename($configPath);
        $commandline = $JSEngine . ' ' . self::OPTIMIZER_FILE_PATH . ' -o ' . $path . ' 1>&2';

        $process = new Process($commandline, $this->getWebRoot());
        
        if (isset($this->config['building_timeout'])) {
            $process->setTimeout($this->config['building_timeout']);
        }
        
        // some workaround when this command is launched from web
        if (isset($_SERVER['PATH'])) {
            $env = $_SERVER;
            if (isset($env['Path'])) {
                unset($env['Path']);
            }
            $process->setEnv($env);
        }
        
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    /**
     * @return string
     */
    protected function getWebRoot()
    {
        return $this->getContainer()->getParameter('oro_require_js.web_root') . DIRECTORY_SEPARATOR;
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
