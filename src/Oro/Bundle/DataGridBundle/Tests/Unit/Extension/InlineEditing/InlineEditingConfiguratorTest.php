<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingConfigurator;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class InlineEditingConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var InlineEditColumnOptionsGuesser|\PHPUnit\Framework\MockObject\MockObject */
    private $guesser;

    /** @var EntityClassNameHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassNameHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var InlineEditingConfigurator */
    private $configurator;

    protected function setUp(): void
    {
        $this->guesser = $this->createMock(InlineEditColumnOptionsGuesser::class);
        $this->entityClassNameHelper = $this->createMock(EntityClassNameHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->configurator = new InlineEditingConfigurator(
            $this->guesser,
            $this->entityClassNameHelper,
            $this->authorizationChecker
        );
    }

    /**
     * @dataProvider paramsDataProvider
     */
    public function testIsInlineEditingSupported(array $params, bool $expected)
    {
        $config = DatagridConfiguration::create($params);

        $this->assertEquals($expected, $this->configurator->isInlineEditingSupported($config));
    }

    public function paramsDataProvider(): array
    {
        return [
            [[], false],
            [['inline_editing' => ['entity_name' => 'Test']], true],
            [['extended_entity_name' => 'Test'], true]
        ];
    }

    public function testConfigureInlineEditingForGrid()
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test'
        ]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT;entity:Test')
            ->willReturn(true);
        $this->entityClassNameHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with('Test')
            ->willReturn('TestSafe');

        $this->configurator->configureInlineEditingForGrid($config);

        $this->assertEquals(
            [
                'extended_entity_name' => 'Test',
                'inline_editing' => [
                    'entity_name' => 'Test',
                    'enable' => false,
                    'behaviour' => 'enable_all',
                    'mobile_enabled' => false,
                    'save_api_accessor' => [
                        'route' => 'oro_api_patch_entity_data',
                        'http_method' => 'PATCH',
                        'default_route_parameters' => [
                            'className' => 'TestSafe'
                        ],
                        'query_parameter_names' => [],
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testConfigureInlineEditingForGridAclDisallowed()
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            'inline_editing' => [
                'enable' => true
            ]
        ]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT;entity:Test')
            ->willReturn(false);
        $this->entityClassNameHelper->expects($this->once())
            ->method('getUrlSafeClassName')
            ->with('Test')
            ->willReturn('TestSafe');

        $this->configurator->configureInlineEditingForGrid($config);

        $this->assertEquals(
            [
                'extended_entity_name' => 'Test',
                'inline_editing' => [
                    'entity_name' => 'Test',
                    'enable' => false,
                    'behaviour' => 'enable_all',
                    'mobile_enabled' => false,
                    'save_api_accessor' => [
                        'route' => 'oro_api_patch_entity_data',
                        'http_method' => 'PATCH',
                        'default_route_parameters' => [
                            'className' => 'TestSafe'
                        ],
                        'query_parameter_names' => [],
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testConfigureInlineEditingForColumn()
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            'inline_editing' => [
                'entity_name' => 'Test',
                'enable' => true,
                'behaviour' => 'enable_all'
            ],
            'columns' => [
                'column1' => [
                    'label' => 'Column 1'
                ]
            ]
        ]);

        $this->guesser->expects($this->once())
            ->method('getColumnOptions')
            ->with('column1', 'Test', ['label' => 'Column 1'], 'enable_all')
            ->willReturn(['inline_editing' => ['enable' => true]]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', new FieldVote(new ObjectIdentity('entity', 'Test'), 'column1'))
            ->willReturn(true);

        $this->configurator->configureInlineEditingForColumn($config, 'column1');

        $this->assertEquals(
            [
                'extended_entity_name' => 'Test',
                'inline_editing' => [
                    'entity_name' => 'Test',
                    'enable' => true,
                    'behaviour' => 'enable_all'
                ],
                'columns' => [
                    'column1' => [
                        'label' => 'Column 1',
                        'inline_editing' => ['enable' => true]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testConfigureInlineEditingForColumnWhenEditDisallowed()
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            'inline_editing' => [
                'entity_name' => 'Test',
                'enable' => true,
                'behaviour' => 'enable_all'
            ],
            'columns' => [
                'column1' => [
                    'label' => 'Column 1',
                    'inline_editing' => ['enable' => true]
                ]
            ]
        ]);

        $this->guesser->expects($this->once())
            ->method('getColumnOptions')
            ->with('column1', 'Test', ['label' => 'Column 1', 'inline_editing' => ['enable' => true]], 'enable_all')
            ->willReturn(['inline_editing' => ['enable' => true]]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', new FieldVote(new ObjectIdentity('entity', 'Test'), 'column1'))
            ->willReturn(false);

        $this->configurator->configureInlineEditingForColumn($config, 'column1');

        $this->assertEquals(
            [
                'extended_entity_name' => 'Test',
                'inline_editing' => [
                    'entity_name' => 'Test',
                    'enable' => true,
                    'behaviour' => 'enable_all'
                ],
                'columns' => [
                    'column1' => [
                        'label' => 'Column 1',
                        'inline_editing' => ['enable' => false]
                    ]
                ]
            ],
            $config->toArray()
        );
    }

    public function testConfigureInlineEditingForSupportingColumns()
    {
        $config = DatagridConfiguration::create([
            'extended_entity_name' => 'Test',
            'inline_editing' => [
                'entity_name' => 'Test',
                'enable' => true,
                'behaviour' => 'enable_all'
            ],
            'columns' => [
                'column1' => [
                    'label' => 'Column 1',
                    'inline_editing' => ['enable' => true]
                ]
            ]
        ]);

        $this->guesser->expects($this->once())
            ->method('getColumnOptions')
            ->with('column1', 'Test', ['label' => 'Column 1', 'inline_editing' => ['enable' => true]], 'enable_all')
            ->willReturn(['inline_editing' => ['enable' => true]]);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('EDIT', new FieldVote(new ObjectIdentity('entity', 'Test'), 'column1'))
            ->willReturn(false);

        $this->configurator->configureInlineEditingForSupportingColumns($config);

        $this->assertEquals(
            [
                'extended_entity_name' => 'Test',
                'inline_editing' => [
                    'entity_name' => 'Test',
                    'enable' => true,
                    'behaviour' => 'enable_all'
                ],
                'columns' => [
                    'column1' => [
                        'label' => 'Column 1',
                        'inline_editing' => ['enable' => false]
                    ]
                ]
            ],
            $config->toArray()
        );
    }
}
