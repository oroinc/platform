<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\ChangePasswordType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ChangePasswordSubscriber */
    private $subscriber;

    /** @var ChangePasswordType */
    private $type;

    /** @var PasswordFieldOptionsProvider */
    private $optionsProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->createMock(ChangePasswordSubscriber::class);
        $this->optionsProvider = $this->createMock(PasswordFieldOptionsProvider::class);

        $this->type = new ChangePasswordType($this->subscriber, $this->optionsProvider);
    }

    /**
     * Test buildForm
     */
    public function testBuildForm()
    {
        $builder = $this->createMock(\Symfony\Component\Form\Test\FormBuilderInterface::class);
        $options = [
            'current_password_label' => 'label',
            'plain_password_invalid_message' => 'label',
            'first_options_label' => 'label',
            'first_options_tooltip' => 'label',
            'second_options_label' => 'label'
        ];

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(ChangePasswordSubscriber::class));

        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();

        $this->type->buildForm($builder, $options);
    }

    /**
     * Test defaults
     */
    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('current_password_label'),
                    $this->arrayHasKey('plain_password_invalid_message'),
                    $this->arrayHasKey('first_options_label'),
                    $this->arrayHasKey('second_options_label')
                )
            );
        $this->type->configureOptions($resolver);
    }

    /**
     * Test name
     */
    public function testName()
    {
        $this->assertEquals('oro_change_password', $this->type->getName());
    }
}
