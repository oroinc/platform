<?php

namespace Oro\Bundle\AttachmentBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Form type for File collection
 */
class MultiFileType extends AbstractType
{
    const TYPE = 'oro_attachment_multi_file';

    /** @var EventSubscriberInterface */
    private $eventSubscriber;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     */
    public function setEventSubscriber(EventSubscriberInterface $eventSubscriber): void
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->eventSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => FileItemType::class,
            'error_bubbling' => false,
            'constraints' => [
                new Valid(),
            ],
        ]);
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
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
