<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Knp\Menu\ItemInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\NavigationBundle\Utils\MenuUpdateUtils;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\Repository\ReportRepository;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Adds custom reports to the navigation menu.
 */
class NavigationListener
{
    protected DoctrineHelper $doctrineHelper;
    protected ConfigProvider $entityConfigProvider;
    protected TokenAccessorInterface $tokenAccessor;
    protected AclHelper $aclHelper;
    protected FeatureChecker $featureChecker;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigProvider $entityConfigProvider,
        TokenAccessorInterface $tokenAccessor,
        AclHelper $aclHelper,
        FeatureChecker $featureChecker
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->aclHelper = $aclHelper;
        $this->featureChecker = $featureChecker;
    }

    public function onNavigationConfigure(ConfigureMenuEvent $event): void
    {
        if (!$this->tokenAccessor->hasUser()) {
            return;
        }

        $reportsMenuItem = MenuUpdateUtils::findMenuItem($event->getMenu(), 'reports_tab');
        if (!$reportsMenuItem || !$reportsMenuItem->isDisplayed()) {
            return;
        }

        /** @var ReportRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(Report::class);
        $qb = $repo->getAllReportsBasicInfoQb($this->featureChecker->getDisabledResourcesByType('entities'));

        $reports = $this->aclHelper->apply($qb)->getResult();
        if (!$reports) {
            return;
        }

        $this->addDivider($reportsMenuItem);
        $reportMenuData = [];
        foreach ($reports as $report) {
            $config = $this->entityConfigProvider->getConfig($report['entity']);
            if ($this->checkAvailability($config)) {
                $entityLabel = $config->get('plural_label');
                if (!isset($reportMenuData[$entityLabel])) {
                    $reportMenuData[$entityLabel] = [];
                }
                $reportMenuData[$entityLabel][$report['id']] = $report['name'];
            }
        }
        ksort($reportMenuData);
        $this->buildReportMenu($reportsMenuItem, $reportMenuData);
    }

    /**
     * Checks whether an entity with given config could be shown within navigation of reports.
     */
    protected function checkAvailability(ConfigInterface $config): bool
    {
        return true;
    }

    protected function buildReportMenu(ItemInterface $reportsItem, array $reportData): void
    {
        foreach ($reportData as $entityLabel => $reports) {
            foreach ($reports as $reportId => $reportLabel) {
                $this->getEntityMenuItem($reportsItem, $entityLabel)
                    ->addChild($reportLabel . '_report', [
                        'label'           => $reportLabel,
                        'route'           => 'oro_report_view',
                        'routeParameters' => ['id' => $reportId],
                        'extras'          => ['translate_disabled' => true]
                    ]);
            }
        }
    }

    /**
     * Adds a divider to the given menu
     */
    protected function addDivider(ItemInterface $menu): void
    {
        $menu->addChild('divider-' . random_int(1, 99999))
            ->setLabel('')
            ->setExtra('divider', true)
            ->setExtra('position', 15); // after manage report, we have 10 there
    }

    /**
     * Gets entity menu item for report item.
     */
    protected function getEntityMenuItem(ItemInterface $reportItem, string $entityLabel): ItemInterface
    {
        $entityItemName = $entityLabel . '_report_tab';
        $entityItem = $reportItem->getChild($entityItemName);
        if (!$entityItem) {
            $reportItem->addChild($entityItemName, [
                'label' => $entityLabel,
                'uri'   => '#',
                // after divider, all entities will be added in EntityName:ASC order
                'extras'=> ['position' => 20]
            ]);
            $entityItem = $reportItem->getChild($entityItemName);
        }

        return $entityItem;
    }
}
