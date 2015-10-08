<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\EventsCompilerPass;

class EventsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    const EVENT_NAME = 'test';
    const CLASS_NAME = 'Oro\Bundle\NotificationBundle\Entity\Event';

    public function testCompile()
    {
        $container  = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $dispatcher = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_notification.manager')
            ->will($this->returnValue(true));
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_notification.manager')
            ->will($this->returnValue(true));

        $container->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->will($this->returnValue(true));
        $container->expects($this->once())
            ->method('getParameter')
            ->with('installed')
            ->will($this->returnValue(true));

        $container->expects($this->once())
            ->method('findDefinition')
            ->with('event_dispatcher')
            ->will($this->returnValue($dispatcher));

        $connection = $this->configureConnectionMock();

        $container->expects($this->once())
            ->method('get')
            ->with('doctrine.dbal.default_connection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('fetchAll')
            ->with('SELECT name FROM ' . EventsCompilerPass::EVENT_TABLE_NAME)
            ->will($this->returnValue(array(array('name' => self::EVENT_NAME))));

        $dispatcher->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addListenerService',
                array(self::EVENT_NAME, array('oro_notification.manager', 'process'))
            );

        $compiler = new EventsCompilerPass();
        $compiler->process($container);
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

        $compiler = new EventsCompilerPass();
        $compiler->process($container);
    }

    /**
     * Creates and configure EM mock object
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function configureConnectionMock()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $connection->expects($this->once())
            ->method('connect');
        $connection->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(true));

        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\MySqlSchemaManager')
            ->disableOriginalConstructor()
            ->getMock();
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with([EventsCompilerPass::EVENT_TABLE_NAME])
            ->will($this->returnValue(true));

        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->will($this->returnValue($schemaManager));

        return $connection;
    }
}
