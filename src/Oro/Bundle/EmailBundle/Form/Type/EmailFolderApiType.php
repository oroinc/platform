<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EmailBundle\Model\FolderType;

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
                'oro_entity_identifier',
                [
                    'required' => false,
                    'class'    => 'OroEmailBundle:EmailOrigin',
                    'multiple' => false
                ]
            )
            ->add(
                'fullName',
                'text',
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
                'text',
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
                'choice',
                [
                    'required' => true,
                    'choices'  => [
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
        return 'oro_email_email_folder_api';
    }
}
