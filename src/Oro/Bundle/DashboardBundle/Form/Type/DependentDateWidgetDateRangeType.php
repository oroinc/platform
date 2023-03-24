<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Form type for Date Range widget:
 *  - Used together with {@see CurrentDateWidgetDateRangeType}.
 */
class DependentDateWidgetDateRangeType extends AbstractType
{
    private const NAME = 'oro_type_dependent_date_widget_date_range';

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WidgetDateRangeType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'tooltip' => null,
        ]);

        $resolver->setNormalizer('operator_choices', fn () => $this->getOperatorChoices());
    }

    private function getOperatorChoices(): array
    {
        return [
            $this->translator->trans('oro.dashboard.widget.filter.dependent_date_range.choices.none') =>
                AbstractDateFilterType::TYPE_NONE,
            $this->translator->trans('oro.dashboard.widget.filter.dependent_date_range.choices.starting_at') =>
                AbstractDateFilterType::TYPE_MORE_THAN,
        ];
    }
}
