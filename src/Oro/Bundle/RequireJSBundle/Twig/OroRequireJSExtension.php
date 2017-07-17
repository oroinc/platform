<?php

namespace Oro\Bundle\RequireJSBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;

class OroRequireJSExtension extends \Twig_Extension
{
    const DEFAULT_PROVIDER_ALIAS = 'oro_requirejs_config_provider';

    /** @var EngineInterface */
    protected $templateEngine;

    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $webRoot;

    /** @var boolean */
    protected $buildLogger;

    /**
     * @param EngineInterface    $templateEngine
     * @param ContainerInterface $container
     * @param string             $webRoot
     * @param boolean            $buildLogger
     */
    public function __construct(EngineInterface $templateEngine, ContainerInterface $container, $webRoot, $buildLogger)
    {
        $this->templateEngine = $templateEngine;
        $this->container = $container;
        $this->webRoot = $webRoot;
        $this->buildLogger = $buildLogger;
    }

    /**
     * @return ConfigProviderManager
     */
    protected function getManager()
    {
        return $this->container->get('oro_requirejs.config_provider.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'get_requirejs_config',
                [$this, 'getRequireJSConfig'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'get_requirejs_build_path',
                [$this, 'getRequireJSBuildPath']
            ),
            new \Twig_SimpleFunction(
                'requirejs_build_exists',
                [$this, 'isRequireJSBuildExists']
            ),
            new \Twig_SimpleFunction(
                'requirejs_build_logger',
                [$this, 'getRequireJSBuildLogger']
            ),
        ];
    }

    /**
     * Get require.js main config
     *
     * @param string $alias
     *
     * @return string
     */
    public function getRequireJSConfig($alias = null)
    {
        $provider = $this->getManager()->getProvider($this->getDefaultAliasIfEmpty($alias));

        if (null === $provider) {
            return json_encode([]);
        }

        return $provider->getConfig()->getMainConfig();
    }

    /**
     * Get require.js output file path
     *
     * @param string $alias
     *
     * @return null|string
     */
    public function getRequireJSBuildPath($alias = null)
    {
        $provider = $this->getManager()->getProvider($this->getDefaultAliasIfEmpty($alias));

        if (null === $provider) {
            return null;
        }

        return $provider->getConfig()->getOutputFilePath();
    }

    /**
     * Check if require.js output file exist
     *
     * @param string $alias
     *
     * @return boolean
     */
    public function isRequireJSBuildExists($alias = null)
    {
        $filePath = $this->getRequireJSBuildPath($this->getDefaultAliasIfEmpty($alias));

        return file_exists($this->webRoot . DIRECTORY_SEPARATOR . $filePath);
    }

    /**
     * Get require.js output file path
     *
     * @param string $alias
     *
     * @return string
     */
    public function getRequireJSBuildLogger($alias = null)
    {
        $provider = $this->getManager()->getProvider($this->getDefaultAliasIfEmpty($alias));
        if (null === $provider || !$this->buildLogger) {
            return '';
        }

        $configs = $provider->getConfig()->getBuildConfig();
        $excludeList = array_filter($configs['paths'], function($config) {
            return $config === 'empty:';
        });


        $parameters = [];
        $parameters['excludeList'] = array_keys($excludeList);

        return $this->templateEngine->render('OroRequireJSBundle::requirejs_build_logger.html.twig', $parameters);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'requirejs_extension';
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function getDefaultAliasIfEmpty($alias)
    {
        return $alias ?: static::DEFAULT_PROVIDER_ALIAS;
    }
}
