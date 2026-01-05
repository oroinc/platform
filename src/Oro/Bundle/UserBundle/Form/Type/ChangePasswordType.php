<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ChangePasswordType extends AbstractType
{
    public const NAME = 'oro_change_password';

    /** @var ChangePasswordSubscriber */
    protected $subscriber;

    /** @var PasswordFieldOptionsProvider */
    protected $optionsProvider;

    public function __construct(ChangePasswordSubscriber $subscriber, PasswordFieldOptionsProvider $optionsProvider)
    {
        $this->subscriber = $subscriber;
        $this->optionsProvider = $optionsProvider;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);
        $builder
            ->add(
                'currentPassword',
                PasswordType::class,
                [
                    'required' => false,
                    'label' => $options['current_password_label'],
                    'constraints' => [
                        new UserPassword()
                    ],
                    'mapped' => false,
                ]
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'required' => false,
                    'type' => PasswordType::class,
                    'invalid_message' => $options['plain_password_invalid_message'],
                    'options' => [
                        'attr' => [
                            'class' => 'password-field'
                        ]
                    ],
                    'first_options' => [
                        'label' => $options['first_options_label'],
                        'tooltip' => $this->optionsProvider->getTooltip(),
                        'attr' => [
                            'data-validation' => $this->optionsProvider->getDataValidationOption(),
                        ],
                    ],
                    'second_options' => ['label' => $options['second_options_label'],
                    ],
                    'mapped' => false,
                ]
            );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'inherit_data' => true,
                'current_password_label' => 'oro.user.password.label',
                'plain_password_invalid_message' => 'oro.user.message.password_mismatch',
                'first_options_label' => 'oro.user.new_password.label',
                'second_options_label' => 'oro.user.new_password_re.label',
            ]
        );
    }
}
