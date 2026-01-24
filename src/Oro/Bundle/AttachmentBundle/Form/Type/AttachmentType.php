<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for building attachment forms with file and comment fields.
 *
 * This form type provides a standard interface for creating attachment forms that include
 * a file upload field and an optional comment field. It is used throughout the application
 * to handle attachment creation and editing, with configurable options for file validation
 * and deletion permissions. The form integrates with the FileType form type to provide
 * comprehensive file handling capabilities.
 */
class AttachmentType extends AbstractType
{
    const NAME = 'oro_attachment';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'file',
            FileType::class,
            [
                'label' => 'oro.attachment.file.label',
                'required' => true,
                'checkEmptyFile' => $options['checkEmptyFile'],
                'allowDelete' => $options['allowDelete']
            ]
        );

        $builder->add(
            'comment',
            TextareaType::class,
            [
                'label'    => 'oro.attachment.comment.label',
                'required' => false,
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
                'data_class'         => 'Oro\Bundle\AttachmentBundle\Entity\Attachment',
                'parentEntityClass'  => '',
                'checkEmptyFile'     => false,
                'allowDelete'        => true,
            ]
        );
    }
}
