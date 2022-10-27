<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\ResetType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetTypeTest extends FormIntegrationTestCase
{
    /** @var ResetType */
    private $type;

    /** @var PasswordFieldOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->optionsProvider = $this->createMock(PasswordFieldOptionsProvider::class);

        $this->type = new ResetType(User::class, $this->optionsProvider);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->optionsProvider->expects($this->once())
            ->method('getTooltip')
            ->willReturn('tooltip');

        $builder->expects($this->once())
            ->method('add')
            ->with('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'required'        => true,
                'invalid_message' => 'oro.user.message.password_mismatch',
                'first_options' => [
                    'label' => 'oro.user.password.enter_new_password.label',
                    'hint' => 'tooltip',
                ],
                'second_options'  => [
                    'label' => 'oro.user.password.enter_new_password_again.label',
                ],
                'error_mapping' => [
                    '.' => 'second',
                ]
            ])
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => User::class,
                'csrf_token_id' => 'reset',
                'dynamic_fields_disabled' => true
            ]);

        $this->type->configureOptions($resolver);
    }

    public function testName()
    {
        $this->assertEquals('oro_user_reset', $this->type->getName());
    }

    public function getBlockPrefix()
    {
        $this->assertEquals('oro_user_reset', $this->type->getBlockPrefix());
    }
}
