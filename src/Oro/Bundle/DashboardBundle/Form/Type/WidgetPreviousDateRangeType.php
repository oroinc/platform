<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Form type for enabling comparison with previous date range in widgets.
 *
 * This form type provides a checkbox control that allows users to enable or disable
 * comparison with a previous date range in dashboard widgets. When enabled, widgets
 * can display trend data and comparisons between the current period and a corresponding
 * previous period, helping users identify changes and patterns over time.
 */
class WidgetPreviousDateRangeType extends AbstractType
{
    public const NAME = 'oro_type_widget_previous_date_range';

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
        return CheckboxType::class;
    }
}
