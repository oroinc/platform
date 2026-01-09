<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The dashboard widget configuration converter for choice how to sort a widget data.
 */
class WidgetSortByConverter extends ConfigValueConverterAbstract
{
    protected array $orderToLabelMap = [
        'ASC' => 'oro.dashboard.widget.sort_by.order.asc.label',
        'DESC' => 'oro.dashboard.widget.sort_by.order.desc.label'
    ];

    public function __construct(
        protected ConfigProvider $entityConfigProvider,
        protected TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        if (
            empty($value['property'])
            || !$this->entityConfigProvider->hasConfig($value['className'], $value['property'])
        ) {
            return null;
        }

        return \sprintf(
            '%s %s',
            $this->translator->trans(
                (string)$this->entityConfigProvider
                    ->getConfig($value['className'], $value['property'])
                    ->get('label')
            ),
            $this->translator->trans($this->orderToLabelMap[$value['order']])
        );
    }
}
