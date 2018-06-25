<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction\ResetTranslationsMassAction;
use Oro\Component\Config\Common\ConfigObject;

class ResetTranslationsMassActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResetTranslationsMassAction */
    protected $action;

    protected function setUp()
    {
        $this->action = new ResetTranslationsMassAction();
    }

    public function testConfigureOptions()
    {
        $this->action->setOptions(ActionConfiguration::create([
            ConfigObject::NAME_KEY => 'test-config',
            'data_identifier' => 'id',
        ]));

        /** @var ActionConfiguration $options */
        $options = $this->action->
        getOptions();
        $this->assertArraySubset([
            'handler' => 'oro_translation.mass_action.reset_translation_handler',
            'route' => 'oro_translation_mass_reset',
            'frontend_handle' => 'ajax',
            'route_parameters' => [],
            'data_identifier' => 'id',
            'allowedRequestTypes' => ['POST'],
            'requestType' => 'POST'
        ], $options->toArray(), true);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage "data_identifier"
     */
    public function testRequiredOptions()
    {
        $this->action->setOptions(ActionConfiguration::create([
            ConfigObject::NAME_KEY => 'test-config',
        ]));
    }

    public function testSetOptions()
    {
        $customOptions = [
            ConfigObject::NAME_KEY => 'test-config',
            'data_identifier' => 'id',
            'handler' => 'test.service.id',
            'route' => 'test_route',
            'route_parameters' => ['param1' => 'value1', 'param2' => 'value2'],
            'frontend_handle' => 'test-frontend-handler',
        ];

        $this->action->setOptions(ActionConfiguration::create($customOptions));
        $this->assertArraySubset($customOptions, $this->action->getOptions()->toArray(), true);
    }
}
