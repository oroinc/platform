<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class EnumFieldConfigExtension extends AbstractTypeExtension
{
    protected $eventSubscriber;

    /**
     * @param EventSubscriberInterface $eventSubscriber
     */
    public function __construct(EventSubscriberInterface $eventSubscriber)
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
    public function getExtendedType()
    {
        return ConfigType::class;
    }
}
