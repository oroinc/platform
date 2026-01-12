<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as SymfonyEmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing user email addresses.
 *
 * This form type provides an email field for user email management. It is
 * configured to work with the Email entity and allows optional email input.
 */
class EmailType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            SymfonyEmailType::class,
            array(
                'label' => 'oro.user.email.label',
                'required' => false,
            )
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_user_email';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                 'data_class' => 'Oro\Bundle\UserBundle\Entity\Email',
            )
        );
    }
}
