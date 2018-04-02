<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

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
                OroResizeableRichTextType::class,
                [
                    'required' => true,
                    'label'    => 'oro.comment.message.label',
                    'attr'     => [
                        'class'       => 'comment-text-field',
                        'placeholder' => 'oro.comment.message.placeholder'
                    ],
                    'constraints' => [ new NotBlank() ]
                ]
            )
            ->add(
                'attachment',
                ImageType::class,
                [
                    'label' => 'oro.comment.attachment.label',
                    'required' => false
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
        $builder->addEventSubscriber(new CommentSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'      => Comment::ENTITY_NAME,
                'csrf_token_id'   => 'comment',
                'csrf_protection' => false,
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::FORM_NAME;
    }
}
