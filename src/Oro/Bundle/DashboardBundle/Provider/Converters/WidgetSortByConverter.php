<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class WidgetSortByConverter extends ConfigValueConverterAbstract
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $orderToLabelMap = [
        'ASC' => 'oro.dashboard.widget.sort_by.order.asc.label',
        'DESC' => 'oro.dashboard.widget.sort_by.order.desc.label',
    ];

    /**
     * @param ConfigProvider $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(ConfigProvider $entityConfigProvider, TranslatorInterface $translator)
    {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        if (empty($value['property']) ||
            !$this->entityConfigProvider->hasConfig($value['className'], $value['property'])
        ) {
            return null;
        }

        return sprintf(
            '%s %s',
            $this->translator->trans(
                $this->entityConfigProvider
                    ->getConfig($value['className'], $value['property'])
                    ->get('label')
            ),
            $this->translator->trans($this->orderToLabelMap[$value['order']])
        );
    }
}
