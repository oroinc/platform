<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class EmailFolderApiType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'origin',
                EntityIdentifierType::class,
                [
                    'required' => false,
                    'class'    => 'OroEmailBundle:EmailOrigin',
                    'multiple' => false
                ]
            )
            ->add(
                'fullName',
                TextType::class,
                [
                    'required'    => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    'required'    => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['max' => 255])
                    ]
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'required'    => true,
                    'constraints' => [
                        new Assert\NotBlank()
                    ],
                    'choices'     => [
                        FolderType::INBOX  => FolderType::INBOX,
                        FolderType::SENT   => FolderType::SENT,
                        FolderType::TRASH  => FolderType::TRASH,
                        FolderType::DRAFTS => FolderType::DRAFTS,
                        FolderType::SPAM   => FolderType::SPAM,
                        FolderType::OTHER  => FolderType::OTHER
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_email_email_folder_api';
    }
}
