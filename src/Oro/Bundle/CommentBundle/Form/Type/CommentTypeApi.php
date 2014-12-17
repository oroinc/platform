<?php

namespace Oro\Bundle\CommentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;

class CommentTypeApi extends AbstractType
{
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
                    'label'    => 'oro.note.message.label',
                    'attr'     => [
                        'class' => 'comment-text-field',
                        'placeholder' => 'oro.comment.message.placeholder'
                    ],
                ]
            );

        $builder->addEventSubscriber(new PatchSubscriber());
        #$builder->addEventSubscriber(new CommentSubscriber($this->configManager));
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
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_comment';
    }
}
