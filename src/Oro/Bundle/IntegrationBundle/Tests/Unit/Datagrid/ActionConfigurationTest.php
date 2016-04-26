<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\IntegrationBundle\Datagrid\ActionConfiguration;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ActionConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnConfigForEnabledChannel()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        ]));

        $expected = [
            'activate' => false,
            'delete'   => true
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShouldReturnConfigForDisabledChannel()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord([
            'enabled' => 'disabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        ]));

        $expected = [
            'deactivate' => false,
            'schedule'   => false,
            'delete'     => true
        ];
        $this->assertEquals($expected, $result);
    }

    public function testShouldReturnConfigForEditModeDisallow()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_DISALLOW
        ]));

        $this->assertEquals([
            'activate' => false,
            'delete' => false,
            'deactivate' => false,
        ], $result);
    }
}
