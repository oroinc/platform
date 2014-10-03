<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\MultipleEntityType;
use Symfony\Component\Form\FormView;

class MultipleEntityTypeTest extends \PHPUnit_Framework_TestCase
{
    const PERMISSION_ALLOW    = 'test_permission_allow';
    const PERMISSION_DISALLOW = 'test_permission_disallow';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var MultipleEntityType
     */
    private $type;

    protected function setUp()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getClassMetadata', 'getRepository'))
            ->getMockForAbstractClass();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new MultipleEntityType($em, $this->securityFacade);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_multiple_entity', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))
            ->method('add')
            ->with('added', 'oro_entity_identifier', array('class' => '\stdObject', 'multiple' => true))
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with('removed', 'oro_entity_identifier', array('class' => '\stdObject', 'multiple' => true))
            ->will($this->returnSelf());
        $this->type->buildForm($builder, array('class' => '\stdObject', 'extend' => false));
    }

    public function testSetDefaultOptions()
    {
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $optionsResolver->expects($this->once())
            ->method('setRequired')
            ->with(array('class'));
        $optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'add_acl_resource'           => null,
                    'class'                      => null,
                    'default_element'            => null,
                    'extend'                     => false,
                    'grid_url'                   => null,
                    'initial_elements'           => null,
                    'mapped'                     => false,
                    'selector_window_title'      => null,
                    'extra_config'               => null,
                    'selection_url'              => null,
                    'selection_route'            => null,
                    'selection_route_parameters' => array(),
                )
            );
        $this->type->setDefaultOptions($optionsResolver);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array  $options
     * @param string $expectedKey
     * @param mixed  $expectedValue
     */
    public function testFinishView($options, $expectedKey, $expectedValue)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();

        if (isset($options['add_acl_resource'])) {
            $this->securityFacade->expects($this->once())
                ->method('isGranted')
                ->with($options['add_acl_resource'])
                ->will($this->returnValue($expectedValue));
        } else {
            $this->securityFacade->expects($this->never())
                ->method('isGranted');
        }

        $view = new FormView();
        $this->type->finishView($view, $form, $options);
        $this->assertArrayHasKey($expectedKey, $view->vars);
        $this->assertEquals($expectedValue, $view->vars[$expectedKey]);
    }

    public function optionsDataProvider()
    {
        return array(
            array(
                array(),
                'allow_action',
                true
            ),
            array(
                array('add_acl_resource' => self::PERMISSION_ALLOW),
                'allow_action',
                true
            ),
            array(
                array('add_acl_resource' => self::PERMISSION_DISALLOW),
                'allow_action',
                false
            ),
            array(
                array('grid_url' => '/test'),
                'grid_url',
                '/test'
            ),
            array(
                array(),
                'grid_url',
                null
            ),
            array(
                array('initial_elements' => array()),
                'initial_elements',
                array()
            ),
            array(
                array(),
                'initial_elements',
                null
            ),
            array(
                array('selector_window_title' => 'Select'),
                'selector_window_title',
                'Select'
            ),
            array(
                array(),
                'selector_window_title',
                null
            ),
            array(
                array('default_element' => 'name'),
                'default_element',
                'name'
            ),
            array(
                array(),
                'default_element',
                null
            ),
            array(
                '$formOptions'   => array('grid_url' => 'testUrl'),
                '$expectedKey'   => 'grid_url',
                '$expectedValue' => 'testUrl',
            ),
            array(
                '$formOptions'   => array('selection_url' => 'testUrlSelection'),
                '$expectedKey'   => 'selection_url',
                '$expectedValue' => 'testUrlSelection',
            ),
            array(
                '$formOptions'   => array('selection_route' => 'testRoute'),
                '$expectedKey'   => 'selection_route',
                '$expectedValue' => 'testRoute',
            ),
            array(
                '$formOptions'   => array('selection_route_parameters' => array('testParam1')),
                '$expectedKey'   => 'selection_route_parameters',
                '$expectedValue' => array('testParam1'),
            )
        );
    }
}
