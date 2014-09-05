<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroEntitySelectOrCreateInlineTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

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
        $this->formType = new OroEntitySelectOrCreateInlineType($this->securityFacade);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('\Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($metadata));
        $searchRegistry = $this->getMockBuilder('Oro\Bundle\FormBundle\Autocomplete\SearchRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        return array(
            new EntitySelectOrCreateInlineFormExtension($entityManager, $searchRegistry)
        );
    }

    /**
     * @dataProvider requiredOptionsDataProvider
     * @param array $inputOptions
     * @param string $exceptionMessage
     */
    public function testRequiredOptions(array $inputOptions, $exceptionMessage)
    {
        $this->setExpectedException(
            'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
            $exceptionMessage
        );
        $form = $this->factory->create($this->formType, null, $inputOptions);
        $form->submit(null);
    }

    /**
     * @return array
     */
    public function requiredOptionsDataProvider()
    {
        return array(
            array(
                array(),
                'The required option "grid_name" is missing.'
            )
        );
    }

    /**
     * @dataProvider formTypeDataProvider
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param bool $aclAllowed
     * @param bool $aclExpectedToCall
     * @param array $expectedViewVars
     */
    public function testExecute(
        array $inputOptions,
        array $expectedOptions,
        $aclAllowed,
        $aclExpectedToCall,
        array $expectedViewVars = array()
    ) {
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
        return array(
            'create disabled' => array(
                array(
                    'grid_name' => 'test',
                    'converter' => $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'),
                    'entity_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs' => array(
                        'route_name' => 'test'
                    ),
                    'create_enabled' => false
                ),
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled' => false
                ),
                false,
                false,
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled' => false
                )
            ),
            'create no route' => array(
                array(
                    'grid_name' => 'test',
                    'converter' => $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'),
                    'entity_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs' => array(
                        'route_name' => 'test'
                    ),
                    'create_enabled' => true
                ),
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled' => false
                ),
                false,
                false,
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_enabled' => false
                )
            ),
            'create has route disabled' => array(
                array(
                    'grid_name' => 'test',
                    'converter' => $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'),
                    'entity_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs' => array(
                        'route_name' => 'test'
                    ),
                    'create_enabled' => false,
                    'create_form_route' => 'test',
                ),
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => false
                ),
                false,
                false,
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => false
                )
            ),
            'create enabled acl disallowed' => array(
                array(
                    'grid_name' => 'test',
                    'converter' => $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'),
                    'entity_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs' => array(
                        'route_name' => 'test'
                    ),
                    'create_enabled' => true,
                    'create_form_route' => 'test',
                ),
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => false
                ),
                false,
                true,
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => false
                )
            ),
            'create enabled acl allowed' => array(
                array(
                    'grid_name' => 'test',
                    'converter' => $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'),
                    'entity_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs' => array(
                        'route_name' => 'test'
                    ),
                    'create_enabled' => true,
                    'create_form_route' => 'test',
                ),
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => true
                ),
                true,
                true,
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => true
                )
            ),
            'create enabled acl allowed custom' => array(
                array(
                    'grid_name' => 'test',
                    'converter' => $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface'),
                    'entity_class' => 'Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity',
                    'configs' => array(
                        'route_name' => 'test'
                    ),
                    'create_enabled' => true,
                    'create_form_route' => 'test',
                    'create_form_route_parameters' => array('name' => 'US'),
                    'create_acl' => 'acl',
                ),
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => true,
                    'create_acl' => 'acl',
                    'create_form_route_parameters' => array('name' => 'US'),
                ),
                true,
                true,
                array(
                    'grid_name' => 'test',
                    'existing_entity_grid_id' => 'id',
                    'create_form_route' => 'test',
                    'create_enabled' => true,
                    'create_form_route_parameters' => array('name' => 'US'),
                )
            ),
        );
    }
}
