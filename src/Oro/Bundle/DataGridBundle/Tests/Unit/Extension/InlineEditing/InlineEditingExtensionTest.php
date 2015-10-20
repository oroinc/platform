<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditingExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration as FormatterConfiguration;

/**
 * Class InlineEditingExtensionTest
 * @package Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing
 */
class InlineEditingExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|InlineEditColumnOptionsGuesser
     */
    protected $guesser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var InlineEditingExtension
     */
    protected $extension;

    public function setUp()
    {
        $guesser = 'Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptionsGuesser';
        $this->guesser = $this->getMockBuilder($guesser)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new InlineEditingExtension($this->guesser, $this->securityFacade);
    }

    public function testIsApplicable()
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);
        $this->assertTrue($this->extension->isApplicable($config));

        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => false]]);
        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testVisitMetadata()
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);
        $data = MetadataObject::create([]);

        $this->extension->visitMetadata($config, $data);
        $this->assertEquals(
            $config->offsetGet(Configuration::BASE_CONFIG_KEY),
            $data->offsetGet(Configuration::BASE_CONFIG_KEY)
        );
    }

    public function testProcessConfigsWithWrongConfiguration()
    {
        $config = DatagridConfiguration::create([Configuration::BASE_CONFIG_KEY => ['enable' => true]]);

        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
        $this->extension->processConfigs($config);
    }

    public function testProcessConfigs()
    {
        $entityName = 'OroCRM\Bundle\ContactBundle\Entity\Contact';
        $configValues = [
            Configuration::BASE_CONFIG_KEY => [
                'enable' => true,
                'entity_name' => $entityName,
            ],
            FormatterConfiguration::COLUMNS_KEY => [
                'testText' => ['label' => 'test_text'],
                'testSelect' => [
                    'label' => 'test_select',
                    PropertyInterface::FRONTEND_TYPE_KEY => 'string',
                ],
                'testAnotherText' => [
                    'label' => 'test_config_overwrite',
                    'inline_editing' => ['enable' => false]
                ],
                'id' => ['label' => 'test_black_list'],
                'updatedAt' => ['label' => 'test_black_list'],
                'createdAt' => ['label' => 'test_black_list'],
            ]
        ];
        $config = DatagridConfiguration::create($configValues);

        $callback = function ($columnName, $entity, $column) {
            switch ($columnName) {
                case 'testText':
                case 'testAnotherText':
                case 'id':
                case 'updatedAt':
                case 'createdAt':
                    return [Configuration::BASE_CONFIG_KEY => ['enable' => 'true']];
                case 'testSelect':
                    return [
                        Configuration::BASE_CONFIG_KEY => ['enable' => 'true'],
                        PropertyInterface::FRONTEND_TYPE_KEY => 'select',
                        'choices' => [
                            'one' => 'One',
                            'two' => 'Two',
                        ]
                    ];
            }

            return [];
        };

        $this->guesser->expects($this->any())
            ->method('getColumnOptions')
            ->will($this->returnCallback($callback));

        $this->extension->processConfigs($config);

        $expectedValues = [
            Configuration::BASE_CONFIG_KEY => [
                'enable' => false,
                'entity_name' => $entityName,
                'behaviour' => 'enable_selected',
                'save_api_accessor' => [
                    'route' => 'oro_api_patch_entity',
                    'http_method' => 'PATCH',
                    'default_route_parameters' => ['className' => 'OroCRM_Bundle_ContactBundle_Entity_Contact'],
                    'query_parameter_names' => [],
                ],
            ],
            FormatterConfiguration::COLUMNS_KEY => [
                'testText' => [
                    'label' => 'test_text',
                    Configuration::BASE_CONFIG_KEY => ['enable' => 'true']
                ],
                'testSelect' => [
                    'label' => 'test_select',
                    PropertyInterface::FRONTEND_TYPE_KEY => 'select',
                    Configuration::BASE_CONFIG_KEY => ['enable' => 'true'],
                    'choices' => [
                        'one' => 'One',
                        'two' => 'Two',
                    ]
                ],
                'testAnotherText' => [
                    'label' => 'test_config_overwrite',
                    'inline_editing' => ['enable' => false]
                ],
                'id' => ['label' => 'test_black_list'],
                'updatedAt' => ['label' => 'test_black_list'],
                'createdAt' => ['label' => 'test_black_list'],
            ]
        ];

        $expectedResult = DatagridConfiguration::create($expectedValues);

        $key = Configuration::BASE_CONFIG_KEY;
        $this->assertEquals($config->offsetGet($key), $expectedResult->offsetGet($key));

        $key = FormatterConfiguration::COLUMNS_KEY;
        $this->assertEquals($config->offsetGet($key), $expectedResult->offsetGet($key));
    }
}
