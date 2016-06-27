<?php

namespace Oro\Bundle\RequireJSBundle\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\RequireJSBundle\Provider\ChainConfigProvider;
use Oro\Bundle\RequireJSBundle\DependencyInjection\Compiler\ConfigProviderCompilerPass;

class OroBuildCommand extends ContainerAwareCommand
{
    const BUILD_CONFIG_FILE_NAME    = 'build.js';
    const OPTIMIZER_FILE_PATH       = 'bundles/ororequirejs/lib/r.js';

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
        $requireJSConfig = $this->getContainer()->getParameter('oro_require_js');

        /** @var ChainConfigProvider $chainConfigProvider */
        $chainConfigProvider = $this->getContainer()->get(ConfigProviderCompilerPass::PROVIDER_SERVICE);
        foreach ($chainConfigProvider->getProviders() as $configProvider) {
            foreach ($configProvider->collectAllConfigs() as $key => $configs) {
                $config = $configProvider->collectConfigs($key);
                $output->writeln(sprintf('Generating require.js config for "%s" theme', $key));

                // for some reason built application gets broken with configuration in "oneline-json"
                $configContent = str_replace(',', ",\n", $configs['mainConfig']);
                $this->writeConfigFile(
                    $configContent,
                    $this->getWebRoot() . $configProvider->getConfigFilePath($config)
                );

                $configContent = '(' . json_encode($configs['buildConfig']) . ')';
                $configPath = $this->getWebRoot() . self::BUILD_CONFIG_FILE_NAME;
                $this->writeConfigFile($configContent, $configPath);

                $JSEngine = $this->getJSEngine($requireJSConfig);
                if ($JSEngine) {
                    $output->writeln(sprintf('Running code optimizer for "%s" theme', $key));

                    $process = new Process($this->getCommandline($JSEngine, $configPath), $this->getWebRoot());
                    $process->setTimeout($requireJSConfig['building_timeout']);
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

                    $output->writeln('Cleaning up');

                    if (false === @unlink($configPath)) {
                        throw new \RuntimeException('Unable to remove file ' . $configPath);
                    }

                    $output->writeln(
                        sprintf(
                            '<comment>%s</comment> <info>[file+]</info> %s',
                            date('H:i:s'),
                            realpath(
                                $this->getWebRoot() .
                                $configProvider->getOutputFilePath($config)
                            )
                        )
                    );
                }
            }
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
    protected function writeConfigFile($content, $path)
    {
        $fs = new Filesystem();

        try {
            $fs->dumpFile($path, $content);
        } catch (IOExceptionInterface $e) {
            throw new \RuntimeException('Unable to write file ' . $e->getPath());
        }

        return $this;
    }

    /**
     * @param array $config
     *
     * @return string|null
     */
    protected function getJSEngine(array $config)
    {
        return isset($config['js_engine']) ? $config['js_engine'] : null;
    }

    /**
     * @param $JSEngine
     * @param $configPath
     *
     * @return string
     */
    protected function getCommandline($JSEngine, $configPath)
    {
        $path = dirname($configPath) . DIRECTORY_SEPARATOR . basename($configPath);
        return $JSEngine . ' ' . self::OPTIMIZER_FILE_PATH . ' -o ' . $path . ' 1>&2';
    }
}
