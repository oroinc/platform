<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Form type for selecting which widget items to display.
 *
 * This form type extends the standard choice type to provide a specialized interface
 * for selecting visible items from a widget's available sub-widgets or data items.
 * It allows users to control which items appear in multi-item widgets through a
 * choice-based selection mechanism.
 */
class WidgetItemsChoiceType extends AbstractType
{
    public const NAME = 'oro_type_widget_items_choice';

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
