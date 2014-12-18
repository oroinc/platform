<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class CommentTypeApi extends AbstractType
{
    const FORM_NAME = 'oro_comment_api';

    /** @var  ConfigManager $configManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

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
                    'label'    => 'oro.comment.message.label',
                    'attr'     => [
                        'class' => 'comment-text-field',
                        'placeholder' => 'oro.comment.message.placeholder'
                    ],
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
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
                'csrf_protection'         => false,
                'allow_add'               => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::FORM_NAME;
    }
}
