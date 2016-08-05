<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Board;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\Appearance\AppearanceExtension;
use Oro\Bundle\DataGridBundle\Extension\Board\BoardExtension;
use Oro\Bundle\DataGridBundle\Extension\Board\Configuration;

class BoardExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $restrictionManager;

    /**
     * @var BoardExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->restrictionManager = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $configuration = new Configuration();

        $gridConfigurationHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassNameHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new BoardExtension(
            $this->securityFacade,
            $this->translator,
            $this->restrictionManager,
            $configuration,
            $entityClassNameHelper,
            $gridConfigurationHelper
        );

        $parameters = new ParameterBag(
            [
                AppearanceExtension::APPEARANCE_ROOT_PARAM => [
                    AppearanceExtension::APPEARANCE_TYPE_PARAM => 'board',
                    AppearanceExtension::APPEARANCE_DATA_PARAM => ['id' => 'board-id']
                ]
            ]
        );
        $this->extension->setParameters($parameters);
    }

    public function testIsApplicableWithRestriction()
    {
        $config = DatagridConfiguration::createNamed('grid', [
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                'grid' => [],
                'board' => [],
                'chart' => []
            ]
        ]);
        $this->restrictionManager->expects($this->once())->method('boardViewEnabled')
            ->with($config)
            ->will($this->returnValue(false));
        $this->assertFalse($this->extension->isApplicable($config));
        $this->assertArrayNotHasKey('board', $config->offsetGet(AppearanceExtension::APPEARANCE_CONFIG_PATH));
    }

    public function testVisitMetadata()
    {
        $config = DatagridConfiguration::createNamed('grid', [
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                BoardExtension::CONFIG_PATH => [
                    'board1' => [
                        Configuration::LABEL_KEY => 'board label',
                        Configuration::GROUP_KEY => [
                            Configuration::GROUP_PROPERTY_KEY => 'group_field'
                        ],
                        Configuration::ACL_RESOURCE_KEY => 'update_acl_resource',
                    ]
                ]
            ]
        ]);
        $this->restrictionManager->expects($this->once())->method('boardViewEnabled')->will($this->returnValue(true));
        $this->assertTrue($this->extension->isApplicable($config));

        $processor = $this->getMock('Oro\Bundle\DataGridBundle\Extension\Board\Processor\BoardProcessorInterface');
        $processor->expects($this->once())->method('getName')->will($this->returnValue('default'));
        $processor->expects($this->once())->method('getBoardOptions')->will($this->returnValue(['options']));
        $this->extension->addProcessor($processor);

        $this->translator->expects($this->once())->method('trans')->with('board label')
            ->will($this->returnValue('translated board label'));

        $this->securityFacade->expects($this->once())->method('isGranted')->with('update_acl_resource')
            ->will($this->returnValue(true));

        $data = MetadataObject::create([]);
        $this->extension->visitMetadata($config, $data);

        $path = AppearanceExtension::APPEARANCE_OPTION_PATH;
        $expected = [
            [
                'type' => BoardExtension::APPEARANCE_TYPE,
                'plugin' => Configuration::DEFAULT_PLUGIN,
                'icon' => 'icon-th',
                'id' => 'board1',
                'label' => 'translated board label',
                'group_by' => 'group_field',
                'columns' => ['options'],
                'default_transition' => [
                    'class' => Configuration::DEFAULT_TRANSITION_CLASS,
                    'save_api_accessor' => [
                        'class' => Configuration::DEFAULT_TRANSITION_API_ACCESSOR_CLASS,
                        'route' => Configuration::DEFAULT_ROUTE,
                        'http_method' => 'PATCH',
                        'default_route_parameters' => ['className' => null],
                        'query_parameter_names' => []
                    ],
                    'params' => []
                ],
                'board_view' => Configuration::DEFAULT_BOARD_VIEW,
                'card_view' => Configuration::DEFAULT_CARD_VIEW,
                'column_header_view' => Configuration::DEFAULT_HEADER_VIEW,
                'column_view' => Configuration::DEFAULT_COLUMN_VIEW,
                'readonly' => false,
                'toolbar' => [],
                'additional' => []
            ]
        ];
        $this->assertEquals(
            $expected,
            $data->offsetGetByPath($path)
        );
    }

    public function testVisitMetadataWithWrongProcessor()
    {
        $config = DatagridConfiguration::createNamed('grid', [
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                BoardExtension::CONFIG_PATH => [
                    'board1' => [
                        Configuration::PROCESSOR_KEY => 'non-default'
                    ]
                ]
            ]
        ]);
        $this->restrictionManager->expects($this->once())->method('boardViewEnabled')->will($this->returnValue(true));
        $this->extension->isApplicable($config);
        $this->setExpectedException('Oro\Bundle\DataGridBundle\Exception\NotFoundBoardProcessorException');
        $data = MetadataObject::create([]);
        $this->extension->visitMetadata($config, $data);
    }

    public function testVisitDataSource()
    {
        $config = DatagridConfiguration::createNamed('grid', [
            AppearanceExtension::APPEARANCE_CONFIG_PATH => [
                BoardExtension::CONFIG_PATH => [
                    'board-id' => [
                        Configuration::GROUP_KEY => [
                            Configuration::GROUP_PROPERTY_KEY => 'group_field'
                        ],
                    ]
                ]
            ]
        ]);

        $processor = $this->getMock('Oro\Bundle\DataGridBundle\Extension\Board\Processor\BoardProcessorInterface');
        $processor->expects($this->once())->method('getName')->will($this->returnValue('default'));
        $options = [
            ['ids' => ['in_progress']],
            ['ids' => ['lost']]
        ];
        $processor->expects($this->once())->method('getBoardOptions')->will($this->returnValue($options));
        $this->extension->addProcessor($processor);


        $appearanceData = [
            'id' => 'board-id',
            'board_options' => [
                ['in_progress'],
                ['lost']
            ],
            'property' => 'group_field'
        ];
        $dataSource = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface');
        $processor->expects($this->once())->method('processDatasource')->with(
            $dataSource,
            $appearanceData,
            $config
        );
        $this->restrictionManager->expects($this->once())->method('boardViewEnabled')->will($this->returnValue(true));
        $this->extension->isApplicable($config);

        $this->extension->visitDatasource($config, $dataSource);
    }
}
