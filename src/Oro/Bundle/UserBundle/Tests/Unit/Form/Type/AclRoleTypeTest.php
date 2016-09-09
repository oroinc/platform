<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\Type\AclRoleType;

class AclRoleTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclRoleType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new AclRoleType(array('field' => 'field_config'));
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))->method('add')
            ->with('label', 'text', array('required' => true, 'label' => 'oro.user.role.role.label'));
        $builder->expects($this->at(1))->method('add')
            ->with(
                'appendUsers'
            );
        $this->formType->buildForm($builder, array());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_user_role_form', $this->formType->getName());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())->method('setDefaults')
            ->with(
                array(
                    'data_class' => 'Oro\Bundle\UserBundle\Entity\Role',
                    'intention'  => 'role'
                )
            );
        $this->formType->setDefaultOptions($resolver);
    }
}
