<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\NoteBundle\Form\EventListener\UpdateActivityAssociationsSubscriber;
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

        $builder->addEventSubscriber(new UpdateActivityAssociationsSubscriber());
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->children['contexts']->vars['required'] = true;
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_note';
    }
}
