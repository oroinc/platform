<?php

namespace Oro\Bundle\AttachmentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\File;

class FileType extends AbstractType
{
    const NAME = 'oro_file';

    /**
     * @var EventSubscriberInterface
     */
    protected $eventListener;

    /**
     * @param EventSubscriberInterface $eventListener
     */
    public function setEventListener(EventSubscriberInterface $eventListener)
    {
        $this->eventListener = $eventListener;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'file',
            'file',
            [
                'label' => 'oro.attachment.file.label',
                'required'  => false,
            ]
        );

        $builder->addEventSubscriber($this->eventListener);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\AttachmentBundle\Entity\Attachment'
            ]
        );
    }
}
