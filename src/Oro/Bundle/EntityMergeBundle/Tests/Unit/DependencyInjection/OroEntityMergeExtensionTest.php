<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\EntityMergeBundle\DependencyInjection\OroEntityMergeExtension;

class OroEntityMergeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroEntityMergeExtension
     */
    protected $extension;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroEntityMergeExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }

    /**
     * @dataProvider loadParameterDataProvider
     */
    public function testLoadParameters($parameter)
    {
        $this->extension->load(array(), $this->container);
        $this->assertTrue($this->container->hasParameter($parameter));
    }

    public function loadParameterDataProvider()
    {
        return array(
            'metadata.factory'                 => array(
                'oro_entity_merge.metadata.factory.class'
            ),
            'extension.mass_action.type.merge' => array(
                'oro_entity_merge.mass_action.merge.class'
            ),
        );
    }

    /**
     * @dataProvider loadServiceDataProvider
     */
    public function testLoadServices($service, $class, array $arguments, array $tags, $scope)
    {
        $this->extension->load(array(), $this->container);
        $definition = $this->container->getDefinition($service);

        $this->assertEquals($class, $definition->getClass());
        $this->assertTrue($this->container->hasParameter(trim($class, '%')));

        $this->assertEquals($arguments, $definition->getArguments());
        $this->assertEquals($tags, $definition->getTags());
        $this->assertEquals($scope, $definition->getScope());
    }

    public function loadServiceDataProvider()
    {
        return array(
            'oro_entity_merge.metadata.factory'                 => array(
                'service'   => 'oro_entity_merge.metadata.factory',
                'class'     => '%oro_entity_merge.metadata.factory.class%',
                'arguments' => array(
                    new Reference('oro_entity_config.provider.merge'),
                    new Reference('oro_entity_merge.doctrine_helper'),
                    new Reference('event_dispatcher'),
                ),
                'tags'      => array(),
                'scope'     => 'container'
            ),
            'oro_entity_merge.mass_action.merge' => array(
                'service'   => 'oro_entity_merge.mass_action.merge',
                'class'     => '%oro_entity_merge.mass_action.merge.class%',
                'arguments' => array(),
                'tags'      => array(
                    'oro_datagrid.extension.mass_action.type' => array(
                        array('type' => 'merge')
                    )
                ),
                'scope'     => 'prototype'
            ),
        );
    }
}
