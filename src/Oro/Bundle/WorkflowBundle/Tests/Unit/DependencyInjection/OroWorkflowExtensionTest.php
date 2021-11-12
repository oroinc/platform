<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WorkflowBundle\DependencyInjection\OroWorkflowExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class OroWorkflowExtensionTest extends \PHPUnit\Framework\TestCase
{
    private array $expectedDefinitions = [
        'oro_workflow.configuration.provider.workflow_config',
    ];

    private array $expectedParameters = [
        'oro_workflow.configuration.default_subdirectory',
        'oro_workflow.configuration.default_filename'
    ];

    public function testLoad(): void
    {
        $actualDefinitions = [];
        $actualParameters = [];

        $container = $this->createMock(ContainerBuilder::class);
        $container->expects(self::any())
            ->method('setDefinition')
            ->willReturnCallback(function ($id, Definition $definition) use (&$actualDefinitions) {
                $actualDefinitions[$id] = $definition;
            });
        $container->expects(self::any())
            ->method('setParameter')
            ->willReturnCallback(function ($name, $value) use (&$actualParameters) {
                $actualParameters[$name] = $value;
            });
        $container->expects(self::any())
            ->method('getReflectionClass')
            ->willReturnCallback(static fn ($class) =>  new \ReflectionClass($class));
        $container->expects(self::any())
            ->method('getParameterBag')
            ->willReturn(new ParameterBag($actualParameters));

        $extension = new OroWorkflowExtension();
        $extension->load([], $container);

        foreach ($this->expectedDefinitions as $serviceId) {
            self::assertArrayHasKey($serviceId, $actualDefinitions);
            self::assertNotEmpty($actualDefinitions[$serviceId]);
        }

        foreach ($this->expectedParameters as $parameterName) {
            self::assertArrayHasKey($parameterName, $actualParameters);
            self::assertNotEmpty($actualParameters[$parameterName]);
        }
    }
}
