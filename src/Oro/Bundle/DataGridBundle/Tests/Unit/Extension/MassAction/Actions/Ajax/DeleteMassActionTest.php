<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions\Ajax;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\DeleteMassAction;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Symfony\Component\HttpFoundation\Request;

class DeleteMassActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeleteMassAction */
    protected $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->action = new DeleteMassAction();
    }

    public function testSetOptions()
    {
        $this->action->setOptions(
            ActionConfiguration::create(
                [
                    'name' => 'test-action',
                    'entity_name' => \stdClass::class,
                    'data_identifier' => 'e.id',
                ]
            )
        );

        $this->assertEquals(
            [
                'name' => 'test-action',
                'entity_name' => \stdClass::class,
                'data_identifier' => 'e.id',
                'handler' => 'oro_datagrid.extension.mass_action.handler.delete',
                'frontend_type' => 'delete-mass',
                'frontend_handle' => 'ajax',
                'route' => 'oro_datagrid_mass_action',
                'route_parameters' => [],
                'confirmation' => true,
                MassActionExtension::ALLOWED_REQUEST_TYPES => [Request::METHOD_POST, Request::METHOD_DELETE],
                'requestType' => 'POST'
            ],
            $this->action->getOptions()->toArray()
        );
    }
}
