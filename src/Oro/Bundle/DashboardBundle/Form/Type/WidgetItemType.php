<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type for individual widget item configuration.
 *
 * This form type represents a single item within a widget's item collection,
 * providing fields for item identification, display order, and visibility control.
 * It is typically used as an entry type within a collection form to manage
 * multiple widget items that can be shown, hidden, or reordered.
 */
class WidgetItemType extends AbstractType
{
    const NAME = 'oro_type_widget_item';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('order', HiddenType::class)
            ->add('show', CheckboxType::class, [
                'data' => false,
            ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
