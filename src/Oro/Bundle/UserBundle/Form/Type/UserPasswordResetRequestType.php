<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Represents a form type for requesting a user password reset
 */
class UserPasswordResetRequestType extends AbstractType
{
    const NAME = 'oro_user_password_request';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'required' => true,
                    'constraints' => [
                        new NotBlank()
                    ],
                    'attr' => [
                        'placeholder' => 'Username or Email'
                    ]
                ]
            )
            ->add(
                'frontend',
                HiddenType::class,
                [
                    'data' => 1
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
}
