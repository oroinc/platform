<?php

namespace Oro\Bundle\DataGridBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class FilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $data = $event->getData();
            if (!array_key_exists('type', $data)) {
                $event->getForm()->remove('type');
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_type_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_datagrid_filter';
    }
}
