<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\NoteBundle\Entity\Note;

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
                'oro_resizeable_rich_text',
                [
                    'required' => true,
                    'label'    => 'oro.note.message.label'
                ]
            )
            ->add(
                'attachment',
                'oro_image',
                [
                    'label' => 'oro.note.attachment.label',
                    'required' => false
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
                'data_class'              => Note::ENTITY_NAME,
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
