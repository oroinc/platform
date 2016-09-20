<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\Actions\ResetPasswordMassAction;

class ResetPasswordMassActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResetPasswordMassAction */
    protected $resetAction;

    /** @var ActionConfiguration */
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = ActionConfiguration::createNamed(
            'test',
            [
                'entity_name' => 'test',
                'data_identifier' => 'test'
            ]
        );
    }

    public function testSetOptions()
    {
        $this->resetAction = new ResetPasswordMassAction($this->configuration);
        $this->resetAction->setOptions($this->configuration);

        $options = $this->resetAction->getOptions();
        $this->assertEquals('oro_user_mass_password_reset', $options->offsetGet('route'));
        $this->assertEquals('oro_datagrid.mass_action.reset_password.handler', $options->offsetGet('handler'));
    }
}