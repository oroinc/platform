<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DeleteMassActionHandlerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $service = $this->getContainer()->get('oro_datagrid.extension.mass_action.handler.delete');

        $this->assertInstanceOf(DeleteMassActionHandler::class, $service);
    }
}
