<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class OroEntitySelectOrCreateInlineTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var OroEntitySelectOrCreateInlineType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();


        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($provider));

        $this->config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $provider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->config));

        $this->formType = new OroEntitySelectOrCreateInlineType(
            $this->securityFacade,
            $configManager
        );
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata      = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));

        $handler        = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface');
        $searchRegistry = $this
            ->getMockBuilder('Oro\Bundle\FormBundle\Autocomplete\SearchRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $handler
            ->expects($this->any())
            ->method('getProperties')
            ->will($this->returnValue([]));

        $searchRegistry
            ->expects($this->any())
            ->method('getSearchHandler')
            ->will($this->returnValue($handler));

        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $config        = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $configProvider
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        $config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue('value'));

        return [
            new EntitySelectOrCreateInlineFormExtension(
                $entityManager,
                $searchRegistry,
                $configProvider
            )
        ];
    }

    /**
     * @dataProvider formTypeDataProvider
     *
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param bool  $aclAllowed
     * @param bool  $aclExpectedToCall
     * @param array $expectedViewVars
     */
    public function testExecute(
        array $inputOptions,
        array $expectedOptions,
        $aclAllowed,
        $aclExpectedToCall,
        array $expectedViewVars = []
    ) {
        $this->config
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($argument) use ($inputOptions) {
                        if (array_key_exists($argument, $inputOptions)) {
                            return $inputOptions[$argument];
                        }

                        return null;
                    }
                )
            );

        if ($aclExpectedToCall) {
            if (!empty($expectedOptions['create_acl'])) {
                $this->securityFacade->expects($this->once())
                    ->method('isGranted')
                    ->with($expectedOptions['create_acl'])
                    ->will($this->returnValue($aclAllowed));
            } else {
                $this->securityFacade->expects($this->once())
                    ->method('isGranted')
                    ->with('CREATE', 'Entity:Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity')
                    ->will($this->returnValue($aclAllowed));
            }
        } else {
            $this->securityFacade->expects($this->never())
                ->method('isGranted');
        }

        $form = $this->factory->create($this->formType, null, $inputOptions);
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertTrue($form->getConfig()->hasOption($name), sprintf('Expected option %s not found', $name));
            $this->assertEquals(
                $expectedValue,
                $form->getConfig()->getOption($name),
                sprintf('Option %s value is incorrect', $name)
            );
        }

        $form->submit(null);

        $formView = $form->createView();
        foreach ($expectedViewVars as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $formView->vars, sprintf('View vars %s not found', $name));
            $this->assertEquals($expectedValue, $formView->vars[$name], sprintf('View var %s is incorrect', $name));
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function formTypeDataProvider()
    {
        $converter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');

        return [
            'create disabled'                   => [
                [
                    'grid_widget_route' => 'some_route',
                    'grid_name'      => 'test',
                    'converter'      => $converter,
                    'entity_class'   => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs'        => [
                        'route_name' => 'test'
                    ],
                    'create_enabled' => false
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ],
                false,
                false,
                [
                    'grid_widget_route' => 'some_route',
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ]
            ],
            'create no route'                   => [
                [
                    'grid_name'      => 'test',
                    'converter'      => $converter,
                    'entity_class'   => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs'        => [
                        'route_name' => 'test'
                    ],
                    'create_enabled' => true
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ],
                false,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled'          => false
                ]
            ],
            'create has route disabled'         => [
                [
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => false,
                    'create_form_route' => 'test',
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ],
                false,
                false,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ]
            ],
            'create enabled acl disallowed'     => [
                [
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => true,
                    'create_form_route' => 'test',
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ],
                false,
                true,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => false
                ]
            ],
            'create enabled acl allowed'        => [
                [
                    'grid_name'         => 'test',
                    'converter'         => $converter,
                    'entity_class'      => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs'           => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'    => true,
                    'create_form_route' => 'test',
                ],
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => true
                ],
                true,
                true,
                [
                    'grid_name'               => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route'       => 'test',
                    'create_enabled'          => true
                ]
            ],
            'create enabled acl allowed custom' => [
                [
                    'grid_name'                    => 'test',
                    'grid_parameters'              => ['testParam1' => 1],
                    'grid_render_parameters'       => ['testParam2' => 2],
                    'converter'                    => $converter,
                    'entity_class'                 => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs'                      => [
                        'route_name' => 'test'
                    ],
                    'create_enabled'               => true,
                    'create_form_route'            => 'test',
                    'create_form_route_parameters' => ['name' => 'US'],
                    'create_acl'                   => 'acl',
                ],
                [
                    'grid_name'                    => 'test',
                    'grid_parameters'              => ['testParam1' => 1],
                    'grid_render_parameters'       => ['testParam2' => 2],
                    'existing_entity_grid_id'      => 'id',
                    'create_form_route'            => 'test',
                    'create_enabled'               => true,
                    'create_acl'                   => 'acl',
                    'create_form_route_parameters' => ['name' => 'US'],
                ],
                true,
                true,
                [
                    'grid_name'                    => 'test',
                    'existing_entity_grid_id'      => 'id',
                    'create_form_route'            => 'test',
                    'create_enabled'               => true,
                    'create_form_route_parameters' => ['name' => 'US'],
                ]
            ],
        ];
    }
}
