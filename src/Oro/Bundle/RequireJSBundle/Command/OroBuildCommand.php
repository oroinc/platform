<?php

namespace Oro\Bundle\RequireJSBundle\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        $config = $this->getContainer()->getParameter('oro_require_js');

        /** @var ChainConfigProvider $chainConfigProvider */
        $chainConfigProvider = $this->getContainer()->get(ConfigProviderCompilerPass::PROVIDER_SERVICE);
        foreach ($chainConfigProvider->getProviders() as $configProvider) {
            foreach ($configProvider->generateBuildConfigs() as $theme => $themeConfigs) {
                $output->writeln(sprintf('Generating require.js config for "%s" theme', $theme));

                // for some reason built application gets broken with configuration in "oneline-json"
                $configContent = str_replace(',', ",\n", $themeConfigs['mainConfig']);
                $this->writeConfigFile($configContent, $this->getWebRoot() . $configProvider->getConfigFilePath());

                $configContent = '(' . json_encode($themeConfigs['buildConfig']) . ')';
                $configPath = $this->getWebRoot() . self::BUILD_CONFIG_FILE_NAME;
                $this->writeConfigFile($configContent, $configPath);

                if ($JSEngine = $this->getJSEngine($config)) {
                    $output->writeln(sprintf('Running code optimizer for "%s" theme', $theme));

                    $process = new Process($this->getCommandline($JSEngine, $configPath), $this->getWebRoot());
                    $process->setTimeout($config['building_timeout']);
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
                            realpath($this->getWebRoot() . $configProvider->getOutputFilePath())
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
        if (false === @file_put_contents($path, $content)) {
            throw new \RuntimeException('Unable to write file ' . $path);
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
        return $JSEngine . ' ' . self::OPTIMIZER_FILE_PATH . ' -o ' . dirname($configPath) . DIRECTORY_SEPARATOR . basename($configPath) . ' 1>&2';
    }
}
