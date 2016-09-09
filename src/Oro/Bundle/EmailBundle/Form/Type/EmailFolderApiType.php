<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
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
