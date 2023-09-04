<?php

namespace Oro\Bundle\EmailBundle\Api\Form\Type;

use Oro\Bundle\EmailBundle\Api\Model\EmailAddress;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailAddress as EmailAddressConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The API form type for the email address.
 */
class EmailAddressType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [new Length(['max' => 320])]
            ])
            ->add('email', TextType::class, [
                'constraints' => [new NotBlank(), new Length(['max' => 255]), new EmailAddressConstraint()]
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailAddress::class
        ]);
    }
}
