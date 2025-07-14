<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\FrontendMassAction;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class FrontendMassActionTest extends TestCase
{
    private FrontendMassAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->action = new FrontendMassAction();
    }

    public function testSetOptions(): void
    {
        $options = [
            'frontend_type' => 'frontend-mass',
            MassActionExtension::ALLOWED_REQUEST_TYPES => [Request::METHOD_GET],
            'requestType' => 'GET',
        ];
        $this->action->setOptions(ActionConfiguration::create($options));
        $this->assertEquals($options, $this->action->getOptions()->toArray());
    }
}
