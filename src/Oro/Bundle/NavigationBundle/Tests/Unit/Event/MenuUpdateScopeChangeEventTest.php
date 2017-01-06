<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\MenuUpdateScopeChangeEvent;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;

class MenuUpdateScopeChangeEventTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGetMenuName()
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class);
        $event = new MenuUpdateScopeChangeEvent('application_menu', $scope);
        $this->assertEquals('application_menu', $event->getMenuName());
    }

    public function testGetScope()
    {
        /** @var Scope $scope */
        $scope = $this->getEntity(Scope::class);
        $event = new MenuUpdateScopeChangeEvent('application_menu', $scope);
        $this->assertSame($scope, $event->getScope());
    }
}
