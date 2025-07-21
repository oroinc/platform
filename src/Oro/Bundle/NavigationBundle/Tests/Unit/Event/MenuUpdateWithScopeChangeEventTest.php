<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Event;

use Oro\Bundle\NavigationBundle\Event\MenuUpdateWithScopeChangeEvent;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class MenuUpdateWithScopeChangeEventTest extends TestCase
{
    use EntityTrait;

    public function testGetMenuName(): void
    {
        $scope = new Scope();
        $event = new MenuUpdateWithScopeChangeEvent('application_menu', $scope);
        $this->assertEquals('application_menu', $event->getMenuName());
    }

    public function testGetScope(): void
    {
        $scope = new Scope();
        $event = new MenuUpdateWithScopeChangeEvent('application_menu', $scope);
        $this->assertSame($scope, $event->getScope());
    }
}
