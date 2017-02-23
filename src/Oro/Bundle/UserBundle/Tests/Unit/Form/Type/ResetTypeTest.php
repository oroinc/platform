<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\ResetType;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;

class ResetTypeTest extends FormIntegrationTestCase
{
    /** @var ResetType */
    protected $type;

    /** @var PasswordFieldOptionsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $optionsProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->optionsProvider = $this->getMockBuilder(PasswordFieldOptionsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ResetType(User::class, $this->optionsProvider);
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testBuildForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->optionsProvider->expects($this->once())
            ->method('getTooltip')
            ->willReturn('tooltip');

        $builder->expects($this->exactly(1))
            ->method('add')
            ->with('plainPassword', 'repeated', [
                'type'            => 'password',
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
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => User::class,
                'intention'  => 'reset',
                'dynamic_fields_disabled' => true
            ]);

        $this->type->setDefaultOptions($resolver);
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
