<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection;

use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
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
abstract class ExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Definition[]
     */
    protected $actualDefinitions = [];

    /**
     * @var Alias[]
     */
    protected $actualAliases = [];

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
     * Verifies that aliases have been initialized (defined and not empty)
     *
     * @param array $expectedAliases
     */
    protected function assertAliasesLoaded(array $expectedAliases)
    {
        foreach ($expectedAliases as $serviceId) {
            $this->assertArrayHasKey(
                $serviceId,
                $this->actualAliases,
                sprintf('Definition for "%s" service has not been loaded.', $serviceId)
            );
            $this->assertNotEmpty(
                $this->actualAliases[$serviceId],
                sprintf('Definition for "%s" service is empty.', $serviceId)
            );
        }
    }

    /**
     * Verifies visibility of defined services and aliases
     * NOTE: service visibility can be changed in compiler passes. This method check only config definition
     *
     * @param string[] $expectedPublicServices
     */
    protected function assertPublicServices(array $expectedPublicServices): void
    {
        $loadedServices = array_merge(array_keys($this->actualDefinitions), array_keys($this->actualAliases));
        $publicServices = array_intersect($loadedServices, $expectedPublicServices);

        $this->assertCount(count($expectedPublicServices), $publicServices);

        $errors = [];
        foreach ($publicServices as $serviceId) {
            try {
                $this->assertServiceIsPublic($serviceId);
            } catch (ExpectationFailedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        foreach (array_diff($loadedServices, $publicServices) as $serviceId) {
            try {
                $definitionId = (string)($this->actualAliases[$serviceId] ?? $serviceId);

                // Can't predict check aliases for services from another bundles
                if (!isset($this->actualDefinitions[$definitionId])) {
                    continue;
                }

                $class = $this->actualDefinitions[$definitionId]->getClass() ?? $definitionId;

                // All controllers must be registered as public
                if (is_subclass_of($class, AbstractController::class)
                    || is_subclass_of($class, Controller::class)) {
                    $this->assertServiceIsPublic(
                        $serviceId,
                        sprintf('Definition for "%s" must be public because it is Controller.', $serviceId)
                    );
                    continue;
                }

                // Otherwise service must be a private as default
                $this->assertServiceIsPrivate($serviceId);
            } catch (ExpectationFailedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->assertCount(0, $errors, implode(PHP_EOL, $errors));
    }

    /**
     * Service or alias must be defined as public
     *
     * @param string $serviceId
     * @param string|null $message
     */
    protected function assertServiceIsPublic(string $serviceId, ?string $message = null): void
    {
        $definition = $this->getLoadedDefinition($serviceId);

        $this->assertTrue(
            $definition->isPublic() && !$definition->isPrivate(),
            $message ?? sprintf('Definition for "%s" must be public.', $serviceId)
        );
    }

    /**
     * Service or alias must be defined as private
     * @param string $serviceId
     * @param string|null $message
     */
    protected function assertServiceIsPrivate(string $serviceId, ?string $message = null): void
    {
        $definition = $this->getLoadedDefinition($serviceId);

        $this->assertTrue(
            !$definition->isPublic() || $definition->isPrivate(),
            $message ?? sprintf('Definition for "%s" must be private.', $serviceId)
        );
    }

    /**
     * @param string $serviceId
     * @return Alias|Definition
     */
    protected function getLoadedDefinition(string $serviceId)
    {
        /** @var Definition|Alias $definition */
        $definition = $this->actualDefinitions[$serviceId] ?? $this->actualAliases[$serviceId] ?? null;
        $this->assertNotNull(
            $definition,
            sprintf('Definition for "%s" service or alias has not been loaded.', $serviceId)
        );

        return $definition;
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
     * @return \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected function buildContainerMock()
    {
        return $this->createMock(ContainerBuilder::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder
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
            ->method('setAlias')
            ->will(
                $this->returnCallback(
                    function ($alias, $id) {
                        if (\is_string($id)) {
                            $id = new Alias($id);
                        } elseif (!$id instanceof Alias) {
                            throw new InvalidArgumentException('$id must be a string, or an Alias object.');
                        }

                        $this->actualAliases[$alias] = $id;
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
