<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\ConfigBundle\Provider\Provider;
use Oro\Bundle\ConfigBundle\DependencyInjection\Compiler\SystemConfigurationPass;
use Oro\Bundle\ConfigBundle\Tests\Unit\Fixtures\TestBundle;

class SystemConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemConfigurationPass */
    protected $compiler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    public function setUp()
    {
        $this->compiler  = new SystemConfigurationPass();
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();
    }

    public function tearDown()
    {
        unset($this->compiler);
        unset($this->container);
    }

    /**
     * @dataProvider bundlesProvider
     */
    public function testProcess(array $bundles, $expectedSet)
    {
        $tmp = [];
        foreach ($bundles as $bundle) {
            $tmp[$bundle->getName()] = get_class($bundle);
        }
        $bundles = $tmp;
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles)
            ->registerResource(
                'OroConfigBundle',
                'Resources/config/system_configuration.yml'
            );

        if ($expectedSet) {
            $taggedServices = array('some.service.id' => 'some arguments');

            $this->container->expects($this->once())->method('findTaggedServiceIds')->with(Provider::TAG_NAME)
                ->will($this->returnValue($taggedServices));

            $definitionMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
                ->disableOriginalConstructor()->getMock();
            $this->container->expects($this->exactly(count($taggedServices)))->method('getDefinition')
                ->will($this->returnValue($definitionMock));

            $definitionMock->expects($this->exactly(count($taggedServices)))->method('replaceArgument')
                ->with($this->equalTo(0));
        }

        $this->compiler->process($this->container);
    }

    /**
     * @return array
     */
    public function bundlesProvider()
    {
        return array(
            'no one bundle specified config' => array(
                'bundles'         => array(),
                'should set data' => false
            ),
            'one bundle specified config'    => array(
                'bundles'         => array(new TestBundle()),
                'should set data' => true
            )
        );
    }
}
