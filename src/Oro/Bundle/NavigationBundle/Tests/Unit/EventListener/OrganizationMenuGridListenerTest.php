<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\NavigationBundle\EventListener\MenuGridListener;

class ScopeGridListenerTest extends \PHPUnit_Framework_TestCase
{ # todo @Andrey fix this test
    const ID = 5;

    public function testOnPreBuild()
    {
        $organization = $this->getEntity(Organization::class, ['id' => self::ORGANIZATION_ID]);
        $scope = $this->getEntity(Scope::class, ['id' => self::SCOPE_ID]);
        $scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(self::SCOPE_TYPE, ['organization' => $organization])
            ->willReturn($scope);

        $gridConfig = DatagridConfiguration::create(
            [
                'properties' => [],
                'actions' => []
            ]
        );

        $params = new ParameterBag();
        $params->set('scopeContext', self::ID);
        $event = new PreBuild($gridConfig, $params);
        $listener = new MenuGridListener();
        $listener->onPreBefore($event);

        $this->assertEquals(
            self::ID,
            $gridConfig->offsetGetByPath('[properties][view_link][direct_params][scopeId]')
        );
    }
}
