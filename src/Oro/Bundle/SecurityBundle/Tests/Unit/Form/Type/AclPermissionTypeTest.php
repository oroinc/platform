<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\AclPermissionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class AclPermissionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclPermissionType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new AclPermissionType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $options = array(
            'privileges_config' => array(
                'field_type' => 'grid'
            )
        );
        $builder->expects($this->at(0))->method('add')->with('accessLevel', 'grid', array('required' => false));
        $builder->expects($this->at(1))->method('add')->with('name', HiddenType::class, array('required' => false));
        $this->formType->buildForm($builder, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())->method('setDefaults')
            ->with(
                array(
                    'data_class' => 'Oro\Bundle\SecurityBundle\Model\AclPermission',
                    'privileges_config' => array()
                )
            );
        $this->formType->configureOptions($resolver);
    }
}
