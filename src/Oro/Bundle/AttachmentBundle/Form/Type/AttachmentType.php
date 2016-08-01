<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            'oro_file',
            [
                'label' => 'oro.attachment.file.label',
                'required' => true,
                'checkEmptyFile' => $options['checkEmptyFile'],
                'allowDelete' => $options['allowDelete']
            ]
        );

        $builder->add(
            'comment',
            'textarea',
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\AttachmentBundle\Entity\Attachment',
                'cascade_validation' => true,
                'parentEntityClass'  => '',
                'checkEmptyFile'     => false,
                'allowDelete'        => true,
            ]
        );
    }
}
