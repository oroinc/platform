<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\DependencyInjection\Compiler;


use Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass;

class EmbeddedFormPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldImplementCompilerPassInterface()
    {
        $rc = new \ReflectionClass('Oro\Bundle\EmbeddedFormBundle\DependencyInjection\Compiler\EmbeddedFormPass');
        $this->assertTrue($rc->implementsInterface('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface'));
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenThereIsNoManagerDefinition()
    {
        $pass = new EmbeddedFormPass();

        $container = $this->createContainerBuilderMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_embedded_form.manager')
            ->will($this->returnValue(false));

        $pass->process($container);
    }

    /**
     * @test
     */
    public function shouldDoNothingWhenThereAreNoTags()
    {
        $pass = new EmbeddedFormPass();

        $container = $this->createContainerBuilderMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_embedded_form.manager')
            ->will($this->returnValue(true));

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('oro_embedded_form')
            ->will($this->returnValue([]));

        $pass->process($container);
    }

    /**
     * @test
     */
    public function shouldAddTaggedFormTypes()
    {
        $pass = new EmbeddedFormPass();

        $container = $this->createContainerBuilderMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_embedded_form.manager')
            ->will($this->returnValue(true));

        $managerDefinition = $this->getMock('Symfony\Component\DependencyInjection\Definition');
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_embedded_form.manager')
            ->will($this->returnValue($managerDefinition));

        $tags = [
            'service_1' => [['type' => 'type', 'label' => 'label']],
            'service_2' => [['type' => 'type_2']],
            'service_3' => [[]],
        ];

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('oro_embedded_form')
            ->will($this->returnValue($tags));

        // Adds method calls
        // When label and type are defined
        $managerDefinition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addFormType', [$tags['service_1'][0]['type'], $tags['service_1'][0]['label']])
            ->will($this->returnValue($managerDefinition));

        // When only type defined
        $managerDefinition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addFormType', [$tags['service_2'][0]['type'], $tags['service_2'][0]['type']])
            ->will($this->returnValue($managerDefinition));

        // When only service id is added as a new embedded form type
        $managerDefinition->expects($this->at(2))
            ->method('addMethodCall')
            ->with('addFormType', ['service_3', 'service_3'])
            ->will($this->returnValue($managerDefinition));

        $pass->process($container);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContainerBuilderMock()
    {
        return $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
    }
}
