<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class NoteApiType extends NoteType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventSubscriber(new PatchSubscriber());

        /**
         * TODO: add EventSubscriber
         */
        $builder->add(
            'assoc_note_user',
            'entity',
            [
                'required' => false,
                'class'    => 'Oro\Bundle\UserBundle\Entity\User',
                'label'    => 'oro.note.assoc_note_user.label'
            ]
        );

        //$builder->addEventSubscriber()
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'Oro\Bundle\NoteBundle\Entity\Note',
                'intention'          => 'note',
                //'cascade_validation' => true,
                'csrf_protection'    => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'note';
    }
}
