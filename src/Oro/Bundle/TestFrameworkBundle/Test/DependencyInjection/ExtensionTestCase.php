<?php
declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * ExtensionTestCase class provides an easy way to test dependency injection extensions.
 *
 * Usage:
 * <code>
 * public function testLoad()
 * {
 *     $this->loadExtension(new MyBundleExtension());
 *
 *     $expectedDefinitions = [...];
 *     $this->assertDefinitionsLoaded($expectedDefinitions);
 *
 *     $expectedParameters = [...];
 *     $this->assertParametersLoaded($expectedParameters);
 * }
 * </code>
 */
abstract class ExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Definition[] */
    protected array $actualDefinitions = [];

    /** @var Alias[] */
    protected array $actualAliases = [];

    protected array $actualParameters = [];

    protected array $extensionConfigs = [];

    /**
     * Verifies that definitions have been initialized (defined and not empty)
     *
     * Usage:
     * <code>
     * public function testLoadDefinitions()
     * {
     *     $this->loadExtension(new MyBundleExtension());
     *     $expectedDefinitions = [...];
     *     $this->assertDefinitionsLoaded($expectedDefinitions);
     * }
     * </code>
     */
    protected function assertDefinitionsLoaded(array $expectedDefinitions): void
    {
        foreach ($expectedDefinitions as $serviceId) {
            static::assertArrayHasKey(
                $serviceId,
                $this->actualDefinitions,
                \sprintf('Definition for "%s" service has not been loaded.', $serviceId)
            );
            static::assertNotEmpty(
                $this->actualDefinitions[$serviceId],
                \sprintf('Definition for "%s" service is empty.', $serviceId)
            );
        }
    }

    /**
     * Verifies that aliases have been initialized (defined and not empty)
     */
    protected function assertAliasesLoaded(array $expectedAliases): void
    {
        foreach ($expectedAliases as $serviceId) {
            static::assertArrayHasKey(
                $serviceId,
                $this->actualAliases,
                \sprintf('Definition for "%s" service has not been loaded.', $serviceId)
            );
            static::assertNotEmpty(
                $this->actualAliases[$serviceId],
                \sprintf('Definition for "%s" service is empty.', $serviceId)
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
        $loadedServices = \array_merge(\array_keys($this->actualDefinitions), \array_keys($this->actualAliases));
        $publicServices = \array_intersect($loadedServices, $expectedPublicServices);

        static::assertCount(\count($expectedPublicServices), $publicServices);

        $errors = [];
        foreach ($publicServices as $serviceId) {
            try {
                $this->assertServiceIsPublic($serviceId);
            } catch (ExpectationFailedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        foreach (\array_diff($loadedServices, $publicServices) as $serviceId) {
            try {
                $definitionId = (string)($this->actualAliases[$serviceId] ?? $serviceId);

                // Can't predict check aliases for services from another bundles
                if (!isset($this->actualDefinitions[$definitionId])) {
                    continue;
                }

                $class = $this->actualDefinitions[$definitionId]->getClass() ?? $definitionId;

                // All controllers must be registered as public
                if (is_subclass_of($class, AbstractController::class)) {
                    $this->assertServiceIsPublic(
                        $serviceId,
                        \sprintf('Definition for "%s" must be public because it is Controller.', $serviceId)
                    );
                    continue;
                }

                // Otherwise service must be a private as default
                $this->assertServiceIsPrivate($serviceId);
            } catch (ExpectationFailedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        static::assertCount(0, $errors, \implode(PHP_EOL, $errors));
    }

    /**
     * Service or alias must be defined as public
     */
    protected function assertServiceIsPublic(string $serviceId, ?string $message = null): void
    {
        $definition = $this->getLoadedDefinition($serviceId);

        static::assertTrue(
            $definition->isPublic() && !$definition->isPrivate(),
            $message ?? \sprintf('Definition for "%s" must be public.', $serviceId)
        );
    }

    /**
     * Service or alias must be defined as private
     */
    protected function assertServiceIsPrivate(string $serviceId, ?string $message = null): void
    {
        $definition = $this->getLoadedDefinition($serviceId);

        static::assertTrue(
            !$definition->isPublic() || $definition->isPrivate(),
            $message ?? \sprintf('Definition for "%s" must be private.', $serviceId)
        );
    }

    /**
     * @return Alias|Definition
     */
    protected function getLoadedDefinition(string $serviceId)
    {
        /** @var Definition|Alias $definition */
        $definition = $this->actualDefinitions[$serviceId] ?? $this->actualAliases[$serviceId] ?? null;
        static::assertNotNull(
            $definition,
            \sprintf('Definition for "%s" service or alias has not been loaded.', $serviceId)
        );

        return $definition;
    }

    /**
     * Verifies that parameters have been initialized (is defined)
     *
     * Usage:
     * <code>
     * public function testLoadParameters()
     * {
     *     $this->loadExtension(new MyBundleExtension());
     *     $expectedParameters = [...];
     *     $this->assertParametersLoaded($expectedParameters);
     * }
     * </code>
     */
    protected function assertParametersLoaded(array $expectedParameters): void
    {
        foreach ($expectedParameters as $parameterName) {
            static::assertArrayHasKey(
                $parameterName,
                $this->actualParameters,
                \sprintf('Parameter "%s" has not been loaded.', $parameterName)
            );
        }
    }

    /**
     * @return MockObject|ContainerBuilder
     */
    protected function buildContainerMock(): ContainerBuilder
    {
        return $this->createMock(ContainerBuilder::class);
    }

    /**
     * @return MockObject|ContainerBuilder
     */
    protected function getContainerMock(): ContainerBuilder
    {
        $containerBuilder = $this->buildContainerMock();
        $containerBuilder
            ->method('setDefinition')
            ->willReturnCallback(
                function ($id, Definition $definition) {
                    $this->actualDefinitions[$id] = $definition;
                    return $definition;
                }
            );
        $containerBuilder
            ->method('setAlias')
            ->willReturnCallback(
                function ($alias, $id) {
                    if (\is_string($id)) {
                        $id = new Alias($id);
                    } elseif (!$id instanceof Alias) {
                        throw new InvalidArgumentException('$id must be a string, or an Alias object.');
                    }

                    $this->actualAliases[$alias] = $id;
                }
            );
        $containerBuilder
            ->method('setParameter')
            ->willReturnCallback(
                function ($name, $value) {
                    $this->actualParameters[$name] = $value;
                }
            );
        $containerBuilder
            ->method('prependExtensionConfig')
            ->willReturnCallback(
                function ($name, array $config) {
                    if (!isset($this->extensionConfigs[$name])) {
                        $this->extensionConfigs[$name] = [];
                    }
                    \array_unshift($this->extensionConfigs[$name], $config);
                }
            );
        $containerBuilder
            ->method('getDefinition')
            ->willReturnCallback(
                function (string $id) {
                    return $this->actualDefinitions[$id] ?? null;
                }
            );
        $containerBuilder
            ->method('hasDefinition')
            ->willReturnCallback(
                function (string $id) {
                    return isset($this->actualDefinitions[$id]);
                }
            );
        $containerBuilder->expects(self::any())
            ->method('getReflectionClass')
            ->willReturnCallback(static fn ($class) =>  new \ReflectionClass($class));
        $containerBuilder->expects(self::any())
            ->method('getParameterBag')
            ->willReturn(new ParameterBag($this->actualParameters));

        return $containerBuilder;
    }

    /**
     * Loads provided extension using a mocked container so that the definitions and parameters could be verified later.
     */
    protected function loadExtension(Extension $extension, array $config = []): self
    {
        $extension->load($config, $this->getContainerMock());

        return $this;
    }

    protected function assertExtensionConfigsLoaded(
        array $expectedExtensionConfigs,
        array $expectedExtensionConfigValues = []
    ): void {
        foreach ($expectedExtensionConfigs as $extensionName) {
            static::assertArrayHasKey(
                $extensionName,
                $this->extensionConfigs,
                \sprintf('Config for extension "%s" has not been loaded.', $extensionName)
            );
            static::assertNotEmpty(
                $this->extensionConfigs[$extensionName],
                \sprintf('Config for extension "%s" is empty.', $extensionName)
            );
            if (isset($expectedExtensionConfigValues[$extensionName])) {
                static::assertEquals(
                    $expectedExtensionConfigValues[$extensionName],
                    $this->extensionConfigs[$extensionName],
                    \sprintf('Config for extension "%s" is different than expected.', $extensionName)
                );
            }
        }
    }
}
