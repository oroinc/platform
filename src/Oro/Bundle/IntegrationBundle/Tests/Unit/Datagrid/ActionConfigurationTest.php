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

        $result = $callable(new ResultRecord(array(
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        )));

        $this->assertEquals(array(
            'activate' => false,
        ), $result);
    }

    public function testShouldReturnConfigForDisabledChannel()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord(array(
            'enabled' => 'disabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        )));

        $this->assertEquals(array(
            'deactivate' => false,
            'schedule' => false,
        ), $result);
    }

    public function testShouldReturnConfigForEditModeAllow()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord(array(
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_ALLOW
        )));

        $this->assertEquals(array(
            'activate' => false,
        ), $result);
    }

    public function testShouldReturnConfigForEditModeForceAllow()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord(array(
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_FORCED_ALLOW
        )));

        $this->assertEquals(array(
            'activate' => false,
        ), $result);
    }

    public function testShouldReturnConfigForEditModeDisallow()
    {
        $configuration = new ActionConfiguration();

        $callable = $configuration->getIsSyncAvailableCondition();

        $result = $callable(new ResultRecord(array(
            'enabled' => 'enabled',
            'editMode' => Channel::EDIT_MODE_DISALLOW
        )));

        $this->assertEquals(array(
            'activate' => false,
            'delete' => false,
            'deactivate' => false,
        ), $result);
    }
}
