<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Action\Event;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Action\Event\ConfigureActionsBefore;

class ConfigureActionsBeforeTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $config = DatagridConfiguration::create([]);

        $event = new ConfigureActionsBefore($config);

        $this->assertSame($config, $event->getConfig());
    }
}
