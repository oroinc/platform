<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\EventListener\CommentSubscriber;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Validator\Constraints\HtmlNotBlank;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FormType for the display of add comment functionality
 */
class CommentTypeApi extends AbstractType
{
    /** @var  ConfigManager $configManager */
    protected $configManager;

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
                    'constraints' => [ new HtmlNotBlank() ]
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
                'data_class'      => Comment::class,
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
        return 'oro_comment_api';
    }
}
