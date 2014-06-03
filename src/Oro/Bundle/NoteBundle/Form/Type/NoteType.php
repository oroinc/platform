<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class NoteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'message',
                'textarea',
                [
                    'required' => true,
                    'label'    => 'oro.note.message.label'
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
                'data_class'              => 'Oro\Bundle\NoteBundle\Entity\Note',
                'intention'               => 'note',
                'ownership_disabled'      => true,
                'dynamic_fields_disabled' => true,
                'csrf_protection'         => true,
                'cascade_validation'      => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_note';
    }
}
