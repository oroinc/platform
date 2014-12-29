<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SearchBundle\DependencyInjection\Configuration;
use Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\TestBundle;
use Oro\Component\Config\CumulativeResourceManager;

class OroSearchExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerBuilder */
    private $container;

    protected function setUp()
    {
        $bundle = new TestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle->getName() => get_class($bundle)]);

        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testGetAlias()
    {
        $searchExtension = new OroSearchExtension(array(), $this->container);
        $this->assertEquals('oro_search', $searchExtension->getAlias());
    }

    public function testLoadWithConfigInFiles()
    {
        $searchExtension = new OroSearchExtension();

        $config = array(
            'oro_search' => array(
                'engine'          => 'some-engine',
                'realtime_update' => true
            )
        );

        $searchExtension->load($config, $this->container);
    }

    public function testLoadWithConfigPaths()
    {
        $searchExtension = new OroSearchExtension();

        $config = array(
            'oro_search' => array(
                'engine'          => 'some-engine',
                'realtime_update' => true,
                'entities_config' => array(
                    'Oro\Bundle\DataBundle\Entity\Product' => array(
                        'alias'           => 'test_alias',
                        'search_template' => 'test_template',
                        'fields'          => array(
                            array(
                                'name'          => 'name',
                                'target_type'   => 'text',
                                'target_fields' => array('name', 'all_data')
                            )
                        )
                    )
                )
            )
        );

        $searchExtension->load($config, $this->container);
    }

    public function testLoadWithEngineOrm()
    {
        $searchExtension = new OroSearchExtension();
        $config = array(
            'oro_search' => array(
                'engine'          => Configuration::DEFAULT_ENGINE,
                'realtime_update' => true
            )
        );

        $this->container->setParameter('oro_search.drivers', array('pro_pgSql'));
        $searchExtension->load($config, $this->container);
    }
}
