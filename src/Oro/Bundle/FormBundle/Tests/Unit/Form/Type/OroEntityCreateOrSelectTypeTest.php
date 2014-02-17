<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;

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

    protected $repository;

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

        $this->formType = new OroEntityCreateOrSelectType();
    }

    protected function getExtensions()
    {
        return array(
            new EntityCreateSelectFormExtension($this->managerRegistry)
        );
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param array $expectedViewVars
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $inputOptions,
        array $expectedOptions,
        array $expectedViewVars = array()
    ) {
        $form = $this->factory->create($this->formType, null, $inputOptions);
        foreach ($expectedOptions as $name => $expectedValue) {
            $this->assertTrue($form->getConfig()->hasOption($name));
            $this->assertEquals($expectedValue, $form->getConfig()->getOption($name));
        }

        $form->submit(array());

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
                'inputOptions' => array(
                    'data_class' => self::TEST_ENTITY,
                    'create_entity_form_type' => 'text',
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array('test_entity_widget'),
                ),
                'expectedOptions' => array(
                    'data_class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'create_entity_form_type' => 'text',
                    'create_entity_form_options' => array(),
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array('test_entity_widget'),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array('test_entity_widget'),
                )
            ),
            'custom options' => array(
                'inputOptions' => array(
                    'data_class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                    'create_entity_form_type' => 'entity',
                    'create_entity_form_options' => array(
                        'class' => self::TEST_ENTITY,
                        'property' => 'id'
                    ),
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array('test_entity_widget'),
                ),
                'expectedOptions' => array(
                    'data_class' => self::TEST_ENTITY,
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                    'create_entity_form_type' => 'entity',
                    'create_entity_form_options' => array(
                        'class' => self::TEST_ENTITY,
                        'property' => 'id'
                    ),
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array('test_entity_widget'),
                ),
                'expectedViewVars' => array(
                    'grid_name' => 'test-grid-name',
                    'view_widgets' => array('test_entity_widget'),
                )
            ),
        );
    }
}
