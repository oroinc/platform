<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;
use Oro\Bundle\UserBundle\Form\Type\SetPasswordType;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;

class SetPasswordTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetPasswordType */
    protected $formType;

    /** @var PasswordTooltipProvider */
    protected $tooltipProvider;

    protected function setUp()
    {
        $this->tooltipProvider = $this->getMockBuilder(PasswordTooltipProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formType = new SetPasswordType($this->tooltipProvider);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->once())->method('add')
            ->with('password', 'password', [
                'required'      => true,
                'label'         => 'oro.user.new_password.label',
                'hint'       => null,
                'attr' => [
                    'data-require-length' => '',
                    'data-require-rules' => '',
                ],
                'constraints'   => [
                    new NotBlank(),
                    new PasswordComplexity(),
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
