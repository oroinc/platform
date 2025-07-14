<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\NavigationBundle\EventListener\MenuGridListener;
use PHPUnit\Framework\TestCase;

class MenuGridListenerTest extends TestCase
{
    public function testOnPreBuild(): void
    {
        $config = DatagridConfiguration::create([]);
        $routeParams = ['first_param' => 'foo', 'second_param' => 'bar'];
        $params = new ParameterBag(
            [
                'viewLinkRoute' => 'view_link_route',
                'viewLinkParams' => $routeParams
            ]
        );
        $event = new PreBuild($config, $params);

        $listener = new MenuGridListener();
        $listener->onPreBuild($event);

        $this->assertEquals('view_link_route', $config->offsetGetByPath('[properties][view_link][route]'));
        $this->assertEquals($routeParams, $config->offsetGetByPath('[properties][view_link][direct_params]'));
    }
}
