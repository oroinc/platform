<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigProvider
     */
    protected $provider;

    /**
     * @var EngineInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $engineInterface;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $webRoot;

    protected function setUp()
    {
        $this->engineInterface = $this->getMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->cache = $this->getMock('Doctrine\Common\Cache\CacheProvider');

        $this->config = [
            'build_path'    => './build/path',
            'config'        => [
                'paths' => [
                    'oro/test' => 'test/js/test'
                ]
            ],
            'build'         => [
                'paths' => [
                    'oro/test' => 'empty:'
                ]
            ]
        ];

        $this->webRoot = './web/root';

        $this->provider = new ConfigProvider(
            $this->engineInterface,
            $this->cache,
            $this->config,
            [
                'Oro\Bundle\RequireJSBundle\Tests\Unit\Fixtures\TestBundle\TestBundle'
            ],
            $this->webRoot
        );
    }

    public function testGetConfig()
    {
        $requireConfig = [
            'require-config'    => './web/root/js/require-config',
            'require-lib'       => 'ororequirejs/lib/require'
        ];

        $config = new Config();
        $config->setBuildConfig([
            'paths'             => array_merge($this->config['build']['paths'], $requireConfig),
            'baseUrl'           => $this->webRoot . DIRECTORY_SEPARATOR . 'bundles',
            'out'               => $this->webRoot . DIRECTORY_SEPARATOR . $this->config['build_path'],
            'mainConfigFile'    => $this->webRoot . DIRECTORY_SEPARATOR . ConfigProvider::REQUIREJS_CONFIG_FILE,
            'include'           => array_merge(
                array_keys($requireConfig),
                array_keys($this->config['config']['paths'])
            )
        ]);
        $config->setOutputFilePath($this->config['build_path']);
        $config->setConfigFilePath(ConfigProvider::REQUIREJS_CONFIG_FILE);

        $this->cache
            ->expects($this->once())
            ->method('contains')
            ->with(ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue(false));

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY, [$config]);

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->will($this->returnValue([$config]));

        $this->assertEquals($config, $this->provider->getConfig());
    }
}
