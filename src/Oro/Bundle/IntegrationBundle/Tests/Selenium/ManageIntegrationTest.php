<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;
use Oro\Bundle\IntegrationBundle\Tests\Selenium\Pages\Integrations;

/**
 * Class CreateIntegrationTest
 *
 * @package Oro\Bundle\IntegrationBundle\Tests\Selenium
 */
class ManageIntegrationTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateIntegration()
    {
        $name = 'Magento integration_' . mt_rand(10, 99);

        $login = $this->login();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->add()
            ->setName($name)
            ->setWsdlUrl('http://mage2.dev.magecore.com/index.php/api/v2_soap/index/?wsdl=1')
            ->setApiUser('api_user')
            ->setApiKey('api-key')
            ->setSyncDate('Jan 1, 2013')
            ->checkConnection()
            ->selectWebsite('All web sites')
            ->setAdminUrl('http://mage2.dev.magecore.com/index.php/admin/')
            ->setConnectors(array('Customer connector', 'Order connector', 'Cart connector'))
            ->setTwoWaySync()
            ->setSyncPriority('Remote wins')
            ->save()
            ->assertMessage('Integration saved');

        return $name;
    }

    /**
     * @depends testCreateIntegration
     * @param $name
     * @return string
     */
    public function testDeactivateIntegration($name)
    {
        $login = $this->login();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->filterBy('Name', $name)
            ->open(array($name))
            ->checkStatus('Active')
            ->deactivate()
            ->assertMessage('Integration deactivated')
            ->checkStatus('Inactive')
            ->activate()
            ->assertMessage('Integration activated')
            ->checkStatus('Active');
    }

    /**
     * @depends testCreateIntegration
     * @param $name
     */
    public function testScheduleIntegration($name)
    {
        $login = $this->login();
        /** @var Integrations $login */
        $login->openIntegrations('Oro\Bundle\IntegrationBundle')
            ->filterBy('Name', $name)
            ->open(array($name))
            ->scheduleSync()
            ->checkAddQueueMessage();
    }
}
