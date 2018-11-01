<?php

namespace Oro\Bundle\RequireJSBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\RequireJSBundle\Config\Config;
use Oro\Bundle\RequireJSBundle\Provider\ConfigProvider;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigProvider
     */
    protected $provider;

    /**
     * @var EngineInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $engineInterface;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
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
        $this->engineInterface = $this->createMock('Symfony\Bundle\FrameworkBundle\Templating\EngineInterface');
        $this->cache = $this->createMock('Doctrine\Common\Cache\CacheProvider');

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

        $this->webRoot = './public/root';

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
            'require-config'    => './public/root/js/require-config',
            'require-lib'       => 'npmassets/requirejs/require'
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
            ->method('fetch')
            ->with(ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY)
            ->willReturn(false);
        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(ConfigProvider::REQUIREJS_CONFIG_CACHE_KEY, [$config]);

        $this->assertEquals($config, $this->provider->getConfig());
    }
}
