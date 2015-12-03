<?php

namespace Oro\Bundle\FlexibleEntityBundle\Form\Type;

use Oro\Bundle\FlexibleEntityBundle\Form\EventListener\CollectionTypeSubscriber;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Collection
 */
abstract class CollectionAbstract extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CollectionTypeSubscriber());
    }
}
