<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Listener;

use Oro\Bundle\NavigationBundle\Entity\Listener\MenuUpdatePrePersist;

class MenuUpdatePrePersistTest extends \PHPUnit_Framework_TestCase
{
    public function testPrePersistShouldGenerateKeyIfItsBlank()
    {
        $update = $this->getMock('Oro\Bundle\NavigationBundle\Entity\MenuUpdate');
        $update->expects($this->once())
            ->method('getKey')
            ->willReturn(null);
        $update->expects($this->once())
            ->method('setKey');

        $listener = new MenuUpdatePrePersist();
        $listener->prePersist($update);
    }

    public function testPrePersistShouldNotGenerateKeyIfItsNotBlank()
    {
        $update = $this->getMock('Oro\Bundle\NavigationBundle\Entity\MenuUpdate');
        $update->expects($this->once())
            ->method('getKey')
            ->willReturn('test_key');
        $update->expects($this->never())
            ->method('setKey');

        $listener = new MenuUpdatePrePersist();
        $listener->prePersist($update);
    }
}
