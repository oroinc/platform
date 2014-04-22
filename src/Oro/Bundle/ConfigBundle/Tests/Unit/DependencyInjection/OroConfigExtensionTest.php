<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

use Oro\Component\Config\CumulativeResourceManager;

class OroConfigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $configuration;

    public function testLoadWithDefaults()
    {
        $this->createEmptyConfiguration();
    }

    public function testLoad()
    {
        CumulativeResourceManager::getInstance()->clear();

        $extension = new OroConfigExtension();
        $configs = array();

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$isCalled) {
                        if ($name == 'oro_config' && is_array($value)) {
                            $isCalled = true;
                        }
                    }
                )
            );

        $extension->load($configs, $container);
    }

    protected function createEmptyConfiguration()
    {
        CumulativeResourceManager::getInstance()->clear();

        $this->configuration = new ContainerBuilder();

        $loader = new OroConfigExtension();
        $config = $this->getEmptyConfig();

        $loader->load(array($config), $this->configuration);

        $this->assertTrue($this->configuration instanceof ContainerBuilder);
    }

    /**
     * @return array
     */
    protected function getEmptyConfig()
    {
        $yaml   = '';
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * @param mixed  $value
     * @param string $key
     */
    protected function assertParameter($value, $key)
    {
        $this->assertEquals($value, $this->configuration->getParameter($key), sprintf('%s parameter is correct', $key));
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }
}
