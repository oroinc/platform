<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\RouteProvider;
use PHPUnit\Framework\TestCase;

class RouteProviderTest extends TestCase
{
    private RouteProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new RouteProvider(
            'oro_action_widget_form',
            'oro_action_widget_form_page',
            'oro_action_operation_execute',
            'oro_action_widget_buttons'
        );
    }

    public function testGetWidgetRoute(): void
    {
        $this->assertEquals('oro_action_widget_buttons', $this->provider->getWidgetRoute());
    }

    public function testGetFromDialogRoute(): void
    {
        $this->assertEquals('oro_action_widget_form', $this->provider->getFormDialogRoute());
    }

    public function testGetFromPageRoute(): void
    {
        $this->assertEquals('oro_action_widget_form_page', $this->provider->getFormPageRoute());
    }

    public function testGetExecutionRoute(): void
    {
        $this->assertEquals('oro_action_operation_execute', $this->provider->getExecutionRoute());
    }
}
