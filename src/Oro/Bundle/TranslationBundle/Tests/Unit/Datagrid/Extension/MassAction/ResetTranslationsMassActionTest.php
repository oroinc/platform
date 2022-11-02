<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction\ResetTranslationsMassAction;
use Oro\Component\Config\Common\ConfigObject;

class ResetTranslationsMassActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResetTranslationsMassAction */
    private $action;

    protected function setUp(): void
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
        $options = $this->action->getOptions()->toArray();

        $this->assertSame('oro_translation.mass_action.reset_translation_handler', $options['handler']);
        $this->assertSame('oro_translation_mass_reset', $options['route']);
        $this->assertSame('ajax', $options['frontend_handle']);
        $this->assertSame([], $options['route_parameters']);
        $this->assertSame('id', $options['data_identifier']);
        $this->assertSame(['POST'], $options['allowedRequestTypes']);
        $this->assertSame('POST', $options['requestType']);
    }

    public function testRequiredOptions()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"data_identifier"');

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

        $options = $this->action->getOptions()->toArray();

        foreach ($customOptions as $key => $expectedValue) {
            $this->assertSame($expectedValue, $options[$key]);
        }
    }
}
