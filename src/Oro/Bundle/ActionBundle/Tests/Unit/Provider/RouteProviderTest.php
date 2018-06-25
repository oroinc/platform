<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\RouteProvider;

class RouteProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouteProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new RouteProvider(
            'oro_action_widget_form',
            'oro_action_widget_form_page',
            'oro_action_operation_execute',
            'oro_action_widget_buttons'
        );
    }

    protected function tearDown()
    {
        unset($this->provider);
    }

    public function testGetWidgetRoute()
    {
        $this->assertEquals('oro_action_widget_buttons', $this->provider->getWidgetRoute());
    }

    public function testGetFromDialogRoute()
    {
        $this->assertEquals('oro_action_widget_form', $this->provider->getFormDialogRoute());
    }

    public function testGetFromPageRoute()
    {
        $this->assertEquals('oro_action_widget_form_page', $this->provider->getFormPageRoute());
    }

    public function testGetExecutionRoute()
    {
        $this->assertEquals('oro_action_operation_execute', $this->provider->getExecutionRoute());
    }
}
