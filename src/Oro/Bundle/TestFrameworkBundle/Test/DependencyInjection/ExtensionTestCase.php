<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * ExtensionTestCase class provides an easy way to test extension load.
 *
 * Usage:
 * <code>
 * public function testLoad()
 * {
 *     $this->loadExtension(new MyBundleExtension());
 *
 *     $expectedDefinitions = array();
 *     $this->assertDefinitionsLoaded($expectedDefinitions);
 *
 *     $expectedParameters = array();
 *     $this->assertParametersLoaded($expectedParameters);
 * }
 * </code>
 */
abstract class ExtensionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $actualDefinitions = [];

    /**
     * @var array
     */
    protected $actualParameters = [];

    /**
     * @var array
     */
    protected $extensionConfigs = [];

    /**
     * Verifies that definitions have been initialized (defined and not empty)
     *
     * Usage:
     * <code>
     * public function testLoadDefinitions()
     * {
     *     $this->loadExtension(new MyBundleExtension());
     *     $expectedDefinitions = array();
     *     $this->assertDefinitionsLoaded($expectedDefinitions);
     * }
     * </code>
     *
     * @param array $expectedDefinitions
     */
    protected function assertDefinitionsLoaded(array $expectedDefinitions)
    {
        foreach ($expectedDefinitions as $serviceId) {
            $this->assertArrayHasKey(
                $serviceId,
                $this->actualDefinitions,
                sprintf('Definition for "%s" service has not been loaded.', $serviceId)
            );
            $this->assertNotEmpty(
                $this->actualDefinitions[$serviceId],
                sprintf('Definition for "%s" service is empty.', $serviceId)
            );
        }
    }

    /**
     * Verifies that parameters have been initialized (defined and not empty)
     *
     * Usage:
     * <code>
     * public function testLoadParameters()
     * {
     *     $this->loadExtension(new MyBundleExtension());
     *     $expectedParameters = array();
     *     $this->assertParametersLoaded($expectedParameters);
     * }
     * </code>
     *
     * @param array $expectedParameters
     */
    protected function assertParametersLoaded(array $expectedParameters)
    {
        foreach ($expectedParameters as $parameterName) {
            $this->assertArrayHasKey(
                $parameterName,
                $this->actualParameters,
                sprintf('Parameter "%s" has not been loaded.', $parameterName)
            );
            $this->assertNotEmpty(
                $this->actualParameters[$parameterName],
                sprintf('Parameter "%s" is empty.', $parameterName)
            );
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected function buildContainerMock()
    {
        return $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['setDefinition', 'setParameter', 'prependExtensionConfig'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected function getContainerMock()
    {
        $container = $this->buildContainerMock();
        $container->expects($this->any())
            ->method('setDefinition')
            ->will(
                $this->returnCallback(
                    function ($id, Definition $definition) {
                        $this->actualDefinitions[$id] = $definition;
                    }
                )
            );
        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) {
                        $this->actualParameters[$name] = $value;
                    }
                )
            );
        $container->expects($this->any())
            ->method('prependExtensionConfig')
            ->will(
                $this->returnCallback(
                    function ($name, array $config) {
                        if (!isset($this->extensionConfigs[$name])) {
                            $this->extensionConfigs[$name] = [];
                        }
                        array_unshift($this->extensionConfigs[$name], $config);
                    }
                )
            );

        return $container;
    }

    /**
     * Loads provided extension using a mocked container so that the definitions and parameters could be verified later.
     *
     * @param \Symfony\Component\HttpKernel\DependencyInjection\Extension $extension
     * @param array $config An optional array of configuration values
     * @return $this
     */
    protected function loadExtension(Extension $extension, $config = [])
    {
        $extension->load($config, $this->getContainerMock());

        return $this;
    }

    /**
     * @param array $expectedExtensionConfigs
     */
    protected function assertExtensionConfigsLoaded(array $expectedExtensionConfigs)
    {
        foreach ($expectedExtensionConfigs as $extensionName) {
            $this->assertArrayHasKey(
                $extensionName,
                $this->extensionConfigs,
                sprintf('Config for extension "%s" has not been loaded.', $extensionName)
            );
            $this->assertNotEmpty(
                $this->extensionConfigs[$extensionName],
                sprintf('Config for extension "%s" is empty.', $extensionName)
            );
        }
    }
}
