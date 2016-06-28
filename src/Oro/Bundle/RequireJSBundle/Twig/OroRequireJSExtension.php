<?php

namespace Oro\Bundle\RequireJSBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\RequireJSBundle\Provider\Config;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProviderInterface;

class OroRequireJSExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
     * @param string|null $configKey
     *
     * @return array|string
     */
    public function getRequireJSConfig($configKey = null)
    {
        $provider = $this->getRequireJSConfigProvider($configKey);

        return $provider ? $provider->getMainConfig($configKey) : [];
    }

    /**
     * Get require.js output file path
     *
     * @param string|null $configKey
     *
     * @return null|string
     */
    public function getRequireJSBuildPath($configKey = null)
    {
        $provider = $this->getRequireJSConfigProvider($configKey);

        return $this->getRequireJSConfig($configKey)
            ? $provider->getOutputFilePath()
            : null;
    }

    /**
     * Check if require.js output file exist
     *
     * @param string|null $configKey
     *
     * @return null
     */
    public function isRequireJSBuildExists($configKey = null)
    {
        $filePath = $this->getRequireJSBuildPath($configKey);

        return file_exists(
            $this->container->getParameter('oro_require_js.web_root') .
            DIRECTORY_SEPARATOR . $filePath
        );
    }

    /**
     * Retrieve require js config provider
     *
     * @param string $configKey
     *
     * @return null|ConfigProviderInterface
     */
    protected function getRequireJSConfigProvider($configKey)
    {
        $chainProvider = $this->container->get('oro_requirejs.config_provider.chain');
        foreach ($chainProvider->getProviders() as $provider) {
            if ($provider->getMainConfig($configKey)) {
                return $provider;
            }
        }

        return null;
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
