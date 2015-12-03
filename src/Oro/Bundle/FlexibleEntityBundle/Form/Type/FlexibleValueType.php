<?php

namespace Oro\Bundle\FlexibleEntityBundle\Form\Type;

use Oro\Bundle\FlexibleEntityBundle\Manager\FlexibleManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base flexible value form type
 *
 *
 */
class FlexibleValueType extends AbstractType
{
    /**
     * @var EventSubscriberInterface
     */
    protected $subscriber;

    /**
     * @var string
     */
    protected $valueClass;

    /**
     * Constructor
     *
     * @param FlexibleManager          $flexibleManager
     * @param EventSubscriberInterface $subscriber
     */
    public function __construct(FlexibleManager $flexibleManager, EventSubscriberInterface $subscriber)
    {
        $this->subscriber = $subscriber;
        $this->valueClass = $flexibleManager->getFlexibleValueName();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', HiddenType::class);
        $builder->addEventSubscriber($this->subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => $this->valueClass,
                'cascade_validation' => true
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_flexibleentity_value';
    }
}
