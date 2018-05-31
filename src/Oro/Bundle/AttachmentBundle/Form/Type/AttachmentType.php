<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttachmentType extends AbstractType
{
    const NAME = 'oro_attachment';

    /**
     * {@inheritdoc}
     */
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
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
