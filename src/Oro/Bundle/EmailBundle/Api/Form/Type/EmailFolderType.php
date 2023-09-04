<?php

namespace Oro\Bundle\EmailBundle\Api\Form\Type;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The API form type for the email folder.
 */
class EmailFolderType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Choice([
                        FolderType::INBOX,
                        FolderType::SENT,
                        FolderType::TRASH,
                        FolderType::DRAFTS,
                        FolderType::SPAM,
                        FolderType::OTHER
                    ])
                ]
            ])
            ->add('name', TextType::class, [
                'constraints' => [new NotBlank(), new Length(['max' => 255])]
            ])
            ->add('path', TextType::class, [
                'property_path' => 'fullName',
                'constraints'   => [new NotBlank(), new Length(['max' => 255])]
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailFolder::class
        ]);
    }
}
