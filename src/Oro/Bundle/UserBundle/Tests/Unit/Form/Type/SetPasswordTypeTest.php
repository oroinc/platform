<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\UserBundle\Form\Type\SetPasswordType;

class SetPasswordTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetPasswordType */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new SetPasswordType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->once())->method('add')
            ->with('password', 'password', [
                'required'      => true,
                'label'         => 'oro.user.password.label',
                'constraints'   => [
                    new NotBlank(),
                    new Length(['min' => 2]),
                ],
            ]);
        $this->formType->buildForm($builder, array());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_set_password', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('text', $this->formType->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->formType->setDefaultOptions($resolver);
    }
}
