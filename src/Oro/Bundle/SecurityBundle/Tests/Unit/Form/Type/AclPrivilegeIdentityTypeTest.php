<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SecurityBundle\Form\Type\AclPrivilegeIdentityType;
use Oro\Bundle\SecurityBundle\Form\Type\ObjectLabelType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class AclPrivilegeIdentityTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclPrivilegeIdentityType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new AclPrivilegeIdentityType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->at(0))->method('add')->with('id', HiddenType::class, array('required' => true));
        $builder->expects($this->at(1))->method('add')
            ->with('name', ObjectLabelType::class, array('required' => false));
        $this->formType->buildForm($builder, array());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver->expects($this->once())->method('setDefaults')
            ->with(
                array(
                    'data_class' => 'Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity'
                )
            );
        $this->formType->configureOptions($resolver);
    }
}
