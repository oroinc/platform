<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\SetPasswordType;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SetPasswordTypeTest extends TestCase
{
    private PasswordFieldOptionsProvider&MockObject $optionsProvider;
    private SetPasswordType $formType;

    #[\Override]
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

    public function testBuildForm(): void
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

    public function testGetParent(): void
    {
        $this->assertEquals(TextType::class, $this->formType->getParent());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->formType->configureOptions($resolver);
    }
}
