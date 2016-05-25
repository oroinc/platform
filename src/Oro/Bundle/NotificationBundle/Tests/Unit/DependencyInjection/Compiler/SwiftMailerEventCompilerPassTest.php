<?php


namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\SwiftMailerEventCompilerPass;

class SwiftMailerEventCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    const TEST_SERVICE_ID = 'oro_notification.massnotification_listener';

    /**
     * @var SwiftMailerEventCompilerPass
     */
    private $compiler;

    protected function setUp()
    {
        $this->compiler = new SwiftMailerEventCompilerPass();
    }

    protected function tearDown()
    {
        unset($this->compiler);
    }

    public function testCompile()
    {
        $container  = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(SwiftMailerEventCompilerPass::SERVICE_ALIAS)
            ->will($this->returnValue(true));

        $container->expects($this->once())
            ->method('getDefinition')
            ->with(SwiftMailerEventCompilerPass::SERVICE_ALIAS)
            ->will($this->returnValue($definition));

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(SwiftMailerEventCompilerPass::TAGGED_SERVICE_NAME)
            ->will($this->returnValue(array(self::TEST_SERVICE_ID => null)));
        
        $this->compiler->process($container);
    }

    public function testCompileManagerNotDefined()
    {
        $container  = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_notification.manager')
            ->will($this->returnValue(false));

        $container->expects($this->never())
            ->method('getDefinition');

        $this->compiler->process($container);
    }
}