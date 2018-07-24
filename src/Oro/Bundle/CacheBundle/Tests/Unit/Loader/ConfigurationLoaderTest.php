<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Loader;

use Oro\Bundle\CacheBundle\Loader\ConfigurationLoader;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ConfigurationLoaderTest extends \PHPUnit\Framework\TestCase
{
    const BUNDLE1 = 'Oro\Bundle\CacheBundle\Tests\Unit\Loader\Stub\Bundles\TestBundle1\TestBundle1';
    const BUNDLE2 = 'Oro\Bundle\CacheBundle\Tests\Unit\Loader\Stub\Bundles\TestBundle2\TestBundle2';

    /** @var ConfigurationLoader */
    protected $loader;

    protected function setUp()
    {
        $this->loader = new ConfigurationLoader();
    }

    public function testLoadConfiguration()
    {
        $bundles = [
            'TestBundle1' => self::BUNDLE1,
            'TestBundle2' => self::BUNDLE2,
        ];

        $temporaryContainer = new ContainerBuilder();
        CumulativeResourceManager::getInstance()->clear()->setBundles($bundles);

        $params = new ParameterBag();
        $params->set('test.value', 'my test value');

        $this->loader->setParameterBag($params);

        $this->assertEquals(
            [
                self::BUNDLE1 => [
                    'test_data' => [
                        'test_key' => 'my test value'
                    ]
                ],
            ],
            $this->loader->loadConfiguration(
                'Resources/config/oro/test_config.yml',
                'test_config',
                'test_config',
                $temporaryContainer
            )
        );
    }
}
