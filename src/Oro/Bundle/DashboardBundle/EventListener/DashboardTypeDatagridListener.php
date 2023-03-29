<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds dashboard type column and filter to the dashboards datagrid.
 */
class DashboardTypeDatagridListener
{
    private ManagerRegistry $doctrine;
    private TranslatorInterface $translator;

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $this->doctrine->getRepository(ExtendHelper::buildEnumValueClassName('dashboard_type'));

        // do not add column and filter if the system have only one dashboard type.
        if ($enumRepo->count([]) <= 1) {
            return;
        }

        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            ['dt.name as dashboardType']
        );
        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [['join' => 'dashboard.dashboard_type', 'alias' => 'dt']]
        );

        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                'dashboardType' => [
                    'label' => $this->translator->trans('oro.dashboard.dashboard_type.label'),
                    'editable' => false,
                    'inline_editing' => ['enable' => false]
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[filters][columns]',
            [
                'dashboardType' => [
                    'type' => 'enum',
                    'data_name' => 'dt.id',
                    'enum_code' => 'dashboard_type'
                ]
            ]
        );
    }
}
