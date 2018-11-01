<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WorkflowBundle\DependencyInjection\OroWorkflowExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroWorkflowExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var array
     */
    protected $expectedDefinitions = [
        'oro_workflow.configuration.provider.workflow_config',
    ];

    /**
     * @var array
     */
    protected $expectedParameters = [
        'oro_workflow.configuration.default_subdirectory',
        'oro_workflow.configuration.default_filename'
    ];

    public function testLoad()
    {
        $actualDefinitions = [];
        $actualParameters  = [];

        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->any())
            ->method('setDefinition')
            ->will(
                $this->returnCallback(
                    function ($id, Definition $definition) use (&$actualDefinitions) {
                        $actualDefinitions[$id] = $definition;
                    }
                )
            );
        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$actualParameters) {
                        $actualParameters[$name] = $value;
                    }
                )
            );

        $extension = new OroWorkflowExtension();
        $extension->load([], $container);

        foreach ($this->expectedDefinitions as $serviceId) {
            $this->assertArrayHasKey($serviceId, $actualDefinitions);
            $this->assertNotEmpty($actualDefinitions[$serviceId]);
        }

        foreach ($this->expectedParameters as $parameterName) {
            $this->assertArrayHasKey($parameterName, $actualParameters);
            $this->assertNotEmpty($actualParameters[$parameterName]);
        }
    }
}
