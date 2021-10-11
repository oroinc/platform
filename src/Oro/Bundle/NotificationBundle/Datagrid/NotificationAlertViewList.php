<?php

namespace Oro\Bundle\NotificationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;

/**
 * Provides a default grid view for oro-notification-alerts-grid.
 */
class NotificationAlertViewList extends AbstractViewsList
{
    /**
     * {@inheritDoc}
     */
    protected function getViewsList()
    {
        $unresolvedAlertsView = new View(
            'notificationalert.unresolved',
            [
                'resolved' => [
                    'value' => BooleanFilterType::TYPE_NO
                ]
            ],
            [],
            'system',
            [
                'resolved' => [
                    'renderable' => false
                ]
            ]
        );
        $label = $this->translator->trans(
            'oro.notification.notificationalert.entity_grid_unresolved_view_label',
            [
                '%entity_plural_label%' => $this->translator->trans(
                    'oro.notification.notificationalert.entity_plural_label'
                )
            ]
        );

        $unresolvedAlertsView
            ->setDefault(true)
            ->setLabel($label);

        return [
            $unresolvedAlertsView
        ];
    }
}
