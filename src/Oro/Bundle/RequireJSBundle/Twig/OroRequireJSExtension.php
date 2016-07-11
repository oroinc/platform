<?php

namespace Oro\Bundle\RequireJSBundle\Twig;

use Oro\Bundle\RequireJSBundle\Manager\ConfigProviderManager;

class OroRequireJSExtension extends \Twig_Extension
{
    /**
     * @var ConfigProviderManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $webRoot;

    /**
     * @param ConfigProviderManager $manager
     * @param string                $webRoot
     */
    public function __construct(ConfigProviderManager $manager, $webRoot)
    {
        $this->manager = $manager;
        $this->webRoot = $webRoot;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'get_requirejs_config'      => new \Twig_SimpleFunction(
                'get_requirejs_config',
                [$this, 'getRequireJSConfig'],
                ['is_safe' => ['html']]
            ),
            'get_requirejs_build_path'  => new \Twig_SimpleFunction(
                'get_requirejs_build_path',
                [$this, 'getRequireJSBuildPath']
            ),
            'requirejs_build_exists'    => new \Twig_SimpleFunction(
                'requirejs_build_exists',
                [$this, 'isRequireJSBuildExists']
            ),
        ];
    }

    /**
     * Get require.js main config
     *
     * @param string $alias
     *
     * @return array|string
     */
    public function getRequireJSConfig($alias = 'oro_requirejs_config_provider')
    {
        $provider = $this->manager->getProvider($alias);

        return $provider ? $provider->getConfig()->getMainConfig() : [];
    }

    /**
     * Get require.js output file path
     *
     * @param string $alias
     *
     * @return null|string
     */
    public function getRequireJSBuildPath($alias = 'oro_requirejs_config_provider')
    {
        $provider = $this->manager->getProvider($alias);

        return $provider ? $provider->getConfig()->getOutputFilePath() : null;
    }

    /**
     * Check if require.js output file exist
     *
     * @param string $alias
     *
     * @return boolean
     */
    public function isRequireJSBuildExists($alias = 'oro_requirejs_config_provider')
    {
        $filePath = $this->getRequireJSBuildPath($alias);

        return file_exists($this->webRoot . DIRECTORY_SEPARATOR . $filePath);
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
}
