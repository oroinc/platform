<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttachmentType extends AbstractType
{
    public const NAME = 'oro_attachment';

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
