<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CommentBundle\Entity\Comment;

class CommentType extends AbstractType
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
                    'label'    => 'oro.note.message.label',
                    'attr'     => [
                        'class' => 'comment-text-field',
                        'placeholder' => 'oro.comment.message.placeholder'
                    ],
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
                'data_class'              => Comment::ENTITY_NAME,
                'intention'               => 'comment',
                'ownership_disabled'      => true,
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
        return 'oro_comment_api';
    }
}
