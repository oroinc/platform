<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ReminderBundle\DependencyInjection\Compiler\AddSendProcessorCompilerPass;

class AddSendProcessorCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $container;

    /**
     * @var AddSendProcessorCompilerPass
     */
    private $compiler;

    public function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\\Component\\DependencyInjection\\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->compiler  = new AddSendProcessorCompilerPass();
    }

    public function testProcess()
    {
        $senderDefinition = $this->getMockBuilder('Symfony\\Component\\DependencyInjection\\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(AddSendProcessorCompilerPass::SEND_PROCESSOR_REGISTRY_SERVICE)
            ->will($this->returnValue($senderDefinition));

        $fooProcessorId              = 'foo';
        $barProcessorId              = 'bar';
        $expectedProcessorTags       = array($fooProcessorId => array(), $barProcessorId => array());
        $expectedProcessorReferences = array(new Reference($fooProcessorId), new Reference($barProcessorId));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(AddSendProcessorCompilerPass::SEND_PROCESSOR_TAG)
            ->will($this->returnValue($expectedProcessorTags));

        $senderDefinition->expects($this->once())
            ->method('replaceArgument')
            ->with(0, $expectedProcessorReferences);

        $this->compiler->process($this->container);
    }
}
