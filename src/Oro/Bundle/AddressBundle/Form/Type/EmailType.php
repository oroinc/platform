<?php

namespace Oro\Bundle\AddressBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as SymfonyEmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Provides a form type for managing individual email address entries.
 *
 * This form type handles the creation and editing of single email address records,
 * including an email field and a primary flag. It allows users to specify which email
 * address is the primary contact method and includes a hidden identifier field for
 * proper entity tracking and management.
 */
class EmailType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'email',
                SymfonyEmailType::class,
                array(
                    'label' => 'Email',
                    'required' => true
                )
            )
            ->add(
                'primary',
                RadioType::class,
                array(
                    'label' => 'Primary',
                    'required' => false
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
        return 'oro_email';
    }
}
