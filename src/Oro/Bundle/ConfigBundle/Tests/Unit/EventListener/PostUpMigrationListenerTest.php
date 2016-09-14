<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ConfigBundle\EventListener\PostUpMigrationListener;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdateConfigs()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('clearCache');

        $event = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Event\PostMigrationEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new PostUpMigrationListener($configManager);
        $listener->updateConfigs($event);
    }
}
