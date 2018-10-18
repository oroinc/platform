<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\EventListener\ChangeRoleSubscriber;
use Oro\Bundle\UserBundle\Form\Type\AclRoleType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AclRoleTypeTest extends \PHPUnit\Framework\TestCase
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
            ->with('label', TextType::class, array('required' => true, 'label' => 'oro.user.role.role.label'));
        $builder->expects($this->at(1))->method('add')
            ->with('appendUsers');
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(ChangeRoleSubscriber::class));

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
                    'data_class' => 'Oro\Bundle\UserBundle\Entity\Role',
                    'csrf_token_id' => 'role'
                )
            );
        $this->formType->configureOptions($resolver);
    }
}
