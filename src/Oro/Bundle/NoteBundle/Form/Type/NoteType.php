<?php

namespace Oro\Bundle\NoteBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                OroResizeableRichTextType::class,
                [
                    'required' => true,
                    'label'    => 'oro.note.message.label'
                ]
            )
            ->add(
                'attachment',
                ImageType::class,
                [
                    'label' => 'oro.note.attachment.label',
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'              => Note::ENTITY_NAME,
                'csrf_token_id'           => 'note',
                'ownership_disabled'      => true,
                'dynamic_fields_disabled' => true,
                'csrf_protection'         => true,
                'contexts_options'        => [
                    'constraints' => [
                        new NotBlank()
                    ],
                    'required' => true
                ]
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
