<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\Actions\ResetPasswordMassAction;

class ResetPasswordMassActionTest extends \PHPUnit\Framework\TestCase
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
        $this->resetAction = new ResetPasswordMassAction();
        $this->resetAction->setOptions($this->configuration);

        $options = $this->resetAction->getOptions();

        $this->assertEquals(
            [
                'route' => 'oro_user_mass_password_reset',
                'handler' => 'oro_datagrid.mass_action.forced_password_reset.handler',
                'entity_name' => 'test',
                'data_identifier' => 'test',
                'name' => 'test',
                'route_parameters' => [],
                'frontend_handle' => 'ajax',
                'frontend_type' => 'mass',
                'allowedRequestTypes' => ['POST'],
                'requestType' => 'POST'
            ],
            $options->toArray()
        );
    }
}
