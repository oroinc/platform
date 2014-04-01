<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;
use Symfony\Component\PropertyAccess\PropertyPath;

class OroEntityCreateOrSelectTypeTest extends FormIntegrationTestCase
{
    const TEST_ENTITY = 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity';

    /**
     * @var OroEntityCreateOrSelectType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $this->metadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(self::TEST_ENTITY));

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository->expects($this->any())
            ->method('find')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        return new TestEntity($id);
                    }
                )
            );

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_ENTITY)
            ->will($this->returnValue($this->metadata));
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(self::TEST_ENTITY)
            ->will($this->returnValue($this->repository));

        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->getMockForAbstractClass();
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY)
            ->will($this->returnValue($this->entityManager));
        $this->managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with(self::TEST_ENTITY)
            ->will($this->returnValue($this->repository));

        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($this->isInstanceOf(self::TEST_ENTITY))
            ->will(
                $this->returnCallback(
                    function (TestEntity $entity) {
                        return $entity->getId();
                    }
                )
            );

        $this->formType = new OroEntityCreateOrSelectType($this->doctrineHelper);
    }

    protected function getExtensions()
    {
        return array(
            new EntityCreateSelectFormExtension($this->managerRegistry)
        );
    }

    protected function tearDown()
    {
        unset($this->formType);
        unset($this->managerRegistry);
        unset($this->entityManager);
        unset($this->metadata);
        unset($this->repository);
        unset($this->doctrineHelper);
    }

    /**
     * @param object|null $inputEntity
     * @param array|null $submitData
     * @param object|null $expectedEntity
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param array $expectedViewVars
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        $inputEntity,
        $submitData,
        $expectedEntity,
        array $inputOptions,
        array $expectedOptions,
        array $expectedViewVars = array()
    ) {
        $form = $this->factory->create($this->formType, $inputEntity, $inputOptions);
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertTrue($form->getConfig()->hasOption($name));
            $this->assertEquals($expectedValue, $form->getConfig()->getOption($name));
        }

        $form->submit($submitData);
        $this->assertEquals($expectedEntity, $form->getData());

        $formView = $form->createView();
        foreach ($expectedViewVars as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $formView->vars);
            $this->assertEquals($expectedValue, $formView->vars[$name]);
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider()
    {
        return array(
            'default without entity' => array(
                'inputEntity' => null,
                'submitData' => null,
                'expectedEntity' => null,
                'inputOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'text',
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                        )
                    ),
                ),
                'expectedOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => 'text',
                    'create_entity_form_options' => array(),
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => new PropertyPath('id')),
                            'grid_row_to_route' => array('id' => 'id'),
                        )
                    ),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => null),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                    'value' => array(
                        'new_entity' => null,
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_CREATE
                    ),
                )
            ),
            'default with entity' => array(
                'inputEntity' => new TestEntity(1),
                'submitData' => null,
                'expectedEntity' => null,
                'inputOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'text',
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                        )
                    ),
                ),
                'expectedOptions' => array(
                    'data_class' => null,
                    'class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => 'text',
                    'create_entity_form_options' => array(),
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => new PropertyPath('id')),
                            'grid_row_to_route' => array('id' => 'id'),
                        )
                    ),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => null),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                    'value' => array(
                        'new_entity' => null,
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_CREATE
                    ),
                )
            ),
            'create mode' => array(
                'inputEntity' => null,
                'submitData' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'new_entity' => array('id' => null),
                ),
                'expectedEntity' => new TestEntity(),
                'inputOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'test_entity',
                    'create_entity_form_options' => array(
                        'test_option' => 'default_value'
                    ),
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                        )
                    ),
                ),
                'expectedOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => 'test_entity',
                    'create_entity_form_options' => array(
                        'test_option' => 'default_value'
                    ),
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => new PropertyPath('id')),
                            'grid_row_to_route' => array('id' => 'id'),
                        )
                    ),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => null),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                    'value' => array(
                        'new_entity' => new TestEntity(),
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_CREATE
                    ),
                )
            ),
            'grid mode' => array(
                'inputEntity' => null,
                'submitData' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                ),
                'expectedEntity' => null,
                'inputOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                    'create_entity_form_type' => 'test_entity',
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'key',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                        )
                    ),
                ),
                'expectedOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                    'create_entity_form_type' => 'test_entity',
                    'create_entity_form_options' => array(),
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'key',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => new PropertyPath('id')),
                            'grid_row_to_route' => array('id' => 'id'),
                        )
                    ),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'key',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => null),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                    'value' => array(
                        'new_entity' => null,
                        'existing_entity' => null,
                        'mode' => OroEntityCreateOrSelectType::MODE_GRID
                    ),
                )
            ),
            'view mode' => array(
                'inputEntity' => null,
                'submitData' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                    'existing_entity' => 1
                ),
                'expectedEntity' => new TestEntity(1),
                'inputOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'test_entity',
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('key' => new PropertyPath('id'), 'static' => 'data'),
                            'grid_row_to_route' => array('key' => 'value'),
                        )
                    ),
                ),
                'expectedOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => 'test_entity',
                    'create_entity_form_options' => array(),
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('key' => new PropertyPath('id'), 'static' => 'data'),
                            'grid_row_to_route' => array('key' => 'value'),
                        )
                    ),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('key' => 1, 'static' => 'data'),
                            'grid_row_to_route' => array('key' => 'value'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                    'value' => array(
                        'new_entity' => null,
                        'existing_entity' => new TestEntity(1),
                        'mode' => OroEntityCreateOrSelectType::MODE_VIEW
                    ),
                )
            ),
        );
    }

    /**
     * @param array $options
     * @param string $exception
     * @param string $message
     * @dataProvider executeExceptionDataProvider
     */
    public function testExecuteException(array $options, $exception, $message)
    {
        $this->setExpectedException($exception, $message);

        $this->factory->create($this->formType, null, $options);
    }

    /**
     * @return array
     */
    public function executeExceptionDataProvider()
    {
        return array(
            'no widget route' => array(
                'options' => array(
                    'class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'text',
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array(
                        array()
                    ),
                ),
                'exception' => '\Symfony\Component\Form\Exception\InvalidConfigurationException',
                'message' => 'Widget route name is not defined',
            )
        );
    }
}
