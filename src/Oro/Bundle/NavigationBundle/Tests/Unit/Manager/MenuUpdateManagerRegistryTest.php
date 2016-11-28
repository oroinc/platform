<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Manager;

use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManager;
use Oro\Bundle\NavigationBundle\Manager\MenuUpdateManagerRegistry;

class MenuUpdateManagerRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testAddGetManager()
    {
        $registry = new MenuUpdateManagerRegistry();

        $this->assertNull($registry->getManager('default'));

        $manager = $this->getMockBuilder(MenuUpdateManager::class)->disableOriginalConstructor()->getMock();
        $registry->addManager('default', $manager);

        $this->assertSame($manager, $registry->getManager('default'));
    }
}
