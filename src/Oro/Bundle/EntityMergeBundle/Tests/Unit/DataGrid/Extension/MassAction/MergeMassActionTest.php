<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\DataGrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\EntityConfigBundle\Config\Config as EntityConfig;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\MergeMassAction;

class MergeMassActionTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'Some\Entity';
    private const MAX_ENTITIES_COUNT = 10;

    private MergeMassAction $action;

    protected function setUp(): void
    {
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $entityConfig = new EntityConfig(
            $this->createMock(ConfigIdInterface::class),
            ['max_element_count' => self::MAX_ENTITIES_COUNT]
        );
        $entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS)
            ->willReturn($entityConfig);

        $this->action = new MergeMassAction($entityConfigProvider);
    }

    /**
     * @dataProvider getOptionsDataProvider
     */
    public function testGetOptions(array $actualOptions, array $expectedOptions)
    {
        $this->action->setOptions(ActionConfiguration::create($actualOptions));
        $this->assertEquals($expectedOptions, $this->action->getOptions()->toArray());
    }

    public function getOptionsDataProvider(): array
    {
        return [
            'default_values'  => [
                'actual'   => [
                    'entity_name' => self::ENTITY_CLASS
                ],
                'expected' => [
                    'entity_name'         => self::ENTITY_CLASS,
                    'max_element_count'   => self::MAX_ENTITIES_COUNT,
                    'frontend_handle'     => 'redirect',
                    'handler'             => 'oro_entity_merge.mass_action.data_handler',
                    'frontend_type'       => 'merge-mass',
                    'label'               => 'oro.entity_merge.action.merge',
                    'route'               => 'oro_entity_merge_massaction',
                    'route_parameters'    => [],
                    'data_identifier'     => 'id',
                    'launcherOptions'     => ['iconClassName' => 'fa-random'],
                    'allowedRequestTypes' => ['GET'],
                    'requestType'         => 'GET'
                ]
            ],
            'override_values' => [
                'actual'   => [
                    'entity_name'       => self::ENTITY_CLASS,
                    'frontend_handle'   => 'custom_handler',
                    'handler'           => 'oro_entity_merge.mass_action.data_handler_custom',
                    'frontend_type'     => 'custom-merge-mass',
                    'data_identifier'   => 'code',
                    'icon'              => 'custom',
                    'label'             => 'acme.action.merge',
                    'route'             => 'oro_entity_merge_massaction_custom',
                    'route_parameters'  => ['key' => 'val']
                ],
                'expected' => [
                    'entity_name'         => self::ENTITY_CLASS,
                    'max_element_count'   => self::MAX_ENTITIES_COUNT,
                    'frontend_handle'     => 'custom_handler',
                    'handler'             => 'oro_entity_merge.mass_action.data_handler_custom',
                    'frontend_type'       => 'custom-merge-mass',
                    'data_identifier'     => 'code',
                    'label'               => 'acme.action.merge',
                    'route'               => 'oro_entity_merge_massaction_custom',
                    'route_parameters'    => ['key' => 'val'],
                    'launcherOptions'     => ['iconClassName' => 'fa-custom'],
                    'allowedRequestTypes' => ['GET'],
                    'requestType'         => 'GET'
                ]
            ]
        ];
    }

    public function testMergeMassActionSetOptionShouldThrowExceptionIfClassNameOptionIsEmpty()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Trying to get name of unnamed object');

        $this->action->setOptions(ActionConfiguration::create([]));
    }
}
