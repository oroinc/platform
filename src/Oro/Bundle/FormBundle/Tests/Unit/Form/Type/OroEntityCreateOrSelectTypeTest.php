<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub\TestEntity;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;
use Symfony\Component\PropertyAccess\PropertyPath;

class OroEntityCreateOrSelectTypeTest extends FormIntegrationTestCase
{
    const TEST_ENTITY = 'Oro\Bundle\FormBundle\Tests\Unit\Form\Type\Stub\TestEntity';

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
            ->method('findAll')
            ->will($this->returnValue(array()));

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

        $this->formType = new OroEntityCreateOrSelectType($this->doctrineHelper);
    }

    protected function getExtensions()
    {
        return array(
            new EntityCreateSelectFormExtension($this->managerRegistry)
        );
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
//        foreach ($expectedOptions as $name => $expectedValue) {
//            $this->assertTrue($form->getConfig()->hasOption($name));
//            $this->assertEquals($expectedValue, $form->getConfig()->getOption($name));
//        }

        $form->submit($submitData);
        $this->assertEquals($expectedEntity, $form->getData());

        $formView = $form->createView();
        foreach ($expectedViewVars as $name => $expectedValue) {
            $this->assertArrayHasKey($name, $formView->vars);
            $this->assertEquals($expectedValue, $formView->vars[$name]);
        }
    }

    public function executeDataProvider()
    {
        return array(
            'default options' => array(
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
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => new PropertyPath('id')),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
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
                )
            ),
            'create mode' => array(
                'inputEntity' => null,
                'submitData' => array(
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'new_entity' => array('id' => 1),
                ),
                'expectedEntity' => new TestEntity(1),
                'inputOptions' => array(
                    'class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'test_entity',
                    'grid_name' => 'test-grid-name',
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
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => null),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'existing_entity_grid_id' => 'id',
                    'view_widgets' => array(
                        array(
                            'route_name' => 'test_route',
                            'route_parameters' => array('id' => 1),
                            'grid_row_to_route' => array('id' => 'id'),
                            'widget_alias' => 'oro_entity_create_or_select_test_route',
                        )
                    ),
                )
            ),
        );
    }
}
