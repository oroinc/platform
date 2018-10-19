<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\SetPasswordType;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;

class SetPasswordTypeTest extends \PHPUnit\Framework\TestCase
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
            ->method('getTooltip')
            ->willReturn('test');
        $this->optionsProvider->expects($this->any())
            ->method('getSuggestPasswordOptions')
            ->willReturn(
                [
                    'data-suggest-length' => '',
                    'data-suggest-rules' => '',
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
            ->with('password', PasswordType::class, [
                'required'      => true,
                'label'         => 'oro.user.new_password.label',
                'tooltip' => 'test',
                'attr' => [
                    'data-suggest-length' => '',
                    'data-suggest-rules' => '',
                ],
                'constraints' => [
                    new NotBlank(),
                    new PasswordComplexity($this->optionsProvider->getPasswordComplexityConstraintOptions()),
                ]
            ]);
        $this->formType->buildForm($builder, []);
    }

    public function testGetParent()
    {
        $this->assertEquals(TextType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
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
