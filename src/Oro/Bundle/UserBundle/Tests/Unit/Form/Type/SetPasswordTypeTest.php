<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\SetPasswordType;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;

class SetPasswordTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var SetPasswordType */
    protected $formType;

    /** @var PasswordFieldOptionsProvider */
    protected $optionsProvider;

    protected function setUp()
    {
        $this->optionsProvider = $this->getMockBuilder(PasswordFieldOptionsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionsProvider->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                [
                    'required' => true,
                    'label' => 'oro.user.new_password.label',
                    'hint' => null,
                    'attr' => [
                        'data-suggest-length' => '',
                        'data-suggest-rules' => '',
                    ],
                ]
            );
        $this->formType = new SetPasswordType($this->optionsProvider);
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
                    'data-suggest-length' => '',
                    'data-suggest-rules' => '',
                ],
                'constraints' => [
                    new NotBlank(),
                    new PasswordComplexity()
                ]
            ]);
        $this->formType->buildForm($builder, []);
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
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->formType->configureOptions($resolver);
    }
}
