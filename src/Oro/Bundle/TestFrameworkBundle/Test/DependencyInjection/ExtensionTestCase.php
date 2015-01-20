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
 *     $this->assertDefinitionsMatch($expectedDefinitions);
 *
 *     $expectedParameters = array();
 *     $this->assertParametersMatch($expectedParameters);
 * }
 * </code>
 */
abstract class ExtensionTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var array
     */
    private $actualDefinitions = array();

    /**
     * @var array
     */
    private $actualParameters = array();

    /**
     * Verifies that definitions have been initialized (defined and not empty)
     *
     * Usage:
     * <code>
     * public function testLoadDefinitions()
     * {
     *     $this->loadExtension(new MyBundleExtension());
     *     $expectedDefinitions = array();
     *     $this->assertDefinitionsMatch($expectedDefinitions);
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
     *     $this->assertParametersMatch($expectedParameters);
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
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected function getContainerMock()
    {
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(array('setDefinition', 'setParameter'))
            ->getMock();
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
}
