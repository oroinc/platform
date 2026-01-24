<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Form type for selecting widgets from available options.
 *
 * This form type extends the standard choice type to provide a specialized widget
 * selection interface. It is used in dashboard configuration forms where users need
 * to choose from a list of available widgets to add to their dashboards.
 */
class WidgetChoiceType extends AbstractType
{
    const NAME = 'oro_type_widget_choice';

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
