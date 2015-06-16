<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

use Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber;

class ChangePasswordType extends AbstractType
{
    const NAME = 'oro_change_password';

    /**
     * @var ChangePasswordSubscriber
     */
    protected $subscriber;

    /**
     * @param ChangePasswordSubscriber $subscriber
     */
    public function __construct(ChangePasswordSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->subscriber);

        $builder
            ->add(
                'currentPassword',
                'password',
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
                'repeated',
                [
                    'required' => true,
                    'type' => 'password',
                    'invalid_message' => $options['plain_password_invalid_message'],
                    'options' => [
                        'attr' => [
                            'class' => 'password-field'
                        ]
                    ],
                    'first_options' => ['label' => $options['first_options_label']],
                    'second_options' => ['label' => $options['second_options_label']],
                    'mapped' => false,
                    'cascade_validation' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'inherit_data' => true,
                'cascade_validation' => true,
                'current_password_label' => 'oro.user.password.label',
                'plain_password_invalid_message' => 'The password fields must match.',
                'first_options_label' => 'oro.user.new_password.label',
                'second_options_label' => 'oro.user.new_password_re.label',
            ]
        );
    }
}
