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

        $this->assertEquals([
            'activate' => false,
        ], $result);
    }

    public function testShouldReturnConfigForDisabledChannel()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord([
            'enabled' => 'disabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        ]));

        $this->assertEquals([
            'deactivate' => false,
            'schedule' => false,
        ], $result);
    }

    public function testShouldReturnConfigForEditModeAllow()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        ]));

        $this->assertEquals([
            'activate' => false,
        ], $result);
    }

    public function testShouldReturnConfigForEditModeForceAllow()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord([
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_FORCED_ALLOW
        ]));

        $this->assertEquals([
            'activate' => false,
        ], $result);
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
