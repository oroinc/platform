<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\SetPasswordType;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SetPasswordTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PasswordFieldOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsProvider;

    /** @var SetPasswordType */
    private $formType;

    protected function setUp(): void
    {
        $this->optionsProvider = $this->createMock(PasswordFieldOptionsProvider::class);
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
        $builder = $this->createMock(FormBuilder::class);

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'password',
                PasswordType::class,
                [
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
                ]
            );
        $this->formType->buildForm($builder, []);
    }

    public function testGetParent()
    {
        $this->assertEquals(TextType::class, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->formType->configureOptions($resolver);
    }
}
