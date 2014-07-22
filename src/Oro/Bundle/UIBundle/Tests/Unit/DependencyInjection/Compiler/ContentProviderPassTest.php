<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\ContentProviderPass;
use Symfony\Component\DependencyInjection\Reference;

class ContentProviderPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(ContentProviderPass::CONTENT_PROVIDER_MANAGER_SERVICE)
            ->will($this->returnValue(true));
        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(ContentProviderPass::CONTENT_PROVIDER_MANAGER_SERVICE)
            ->will($this->returnValue($definition));

        $services = array('testId' => array(array('enabled' => false)));
        $containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ContentProviderPass::CONTENT_PROVIDER_TAG)
            ->will($this->returnValue($services));
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addContentProvider', array(new Reference('testId'), false));

        $pass = new ContentProviderPass();
        $pass->process($containerBuilder);
    }
}
