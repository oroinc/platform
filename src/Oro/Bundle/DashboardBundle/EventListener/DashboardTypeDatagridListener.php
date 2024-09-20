<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds dashboard type column and filter to the dashboards datagrid.
 */
class DashboardTypeDatagridListener
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private TranslatorInterface $translator,
        private EnumOptionsProvider $enumOptions
    ) {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        /** @var EnumOptionRepository $enumRepo */
        $enumRepo = $this->doctrine->getRepository(EnumOption::class);

        // do not add column and filter if the system have only one dashboard type.
        if ($enumRepo->count(['enumCode' => 'dashboard_type']) <= 1) {
            return;
        }

        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[columns]',
            [
                'dashboard_type' => [
                    'label' => $this->translator->trans('oro.dashboard.dashboard_type.label'),
                    'frontend_type' => 'select',
                    'data_name' => 'dashboard_type',
                    'choices' => $this->enumOptions->getEnumChoicesByCode('dashboard_type'),
                    'translatable_options' => false,
                    'editable' => false,
                    'inline_editing' => ['enable' => false]
                ]
            ]
        );

        $config->offsetAddToArrayByPath(
            '[filters][columns]',
            [
                'dashboard_type' => [
                    'type' => 'enum',
                    'data_name' => 'dashboard_type',
                    'enum_code' => 'dashboard_type'
                ]
            ]
        );
    }
}
