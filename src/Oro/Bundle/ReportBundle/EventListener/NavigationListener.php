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

class NavigationListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param DoctrineHelper         $doctrineHelper
     * @param ConfigProvider         $entityConfigProvider
     * @param TokenAccessorInterface $tokenAccessor
     * @param AclHelper              $aclHelper
     * @param FeatureChecker         $featureChecker
     */
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

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
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
     * Checks whether an entity with given config could be shown within navigation of reports
     *
     * @param ConfigInterface $config
     *
     * @return bool
     */
    protected function checkAvailability(ConfigInterface $config)
    {
        return true;
    }

    /**
     * Build report menu
     *
     * @param ItemInterface $reportsItem
     * @param array         $reportData
     *  key => entity label
     *  value => array of reports id's and label's
     */
    protected function buildReportMenu(ItemInterface $reportsItem, $reportData)
    {
        foreach ($reportData as $entityLabel => $reports) {
            foreach ($reports as $reportId => $reportLabel) {
                $this->getEntityMenuItem($reportsItem, $entityLabel)
                    ->addChild(
                        $reportLabel . '_report',
                        [
                            'label'           => $reportLabel,
                            'route'           => 'oro_report_view',
                            'routeParameters' => [
                                'id' => $reportId
                            ]
                        ]
                    );
            }
        }
    }

    /**
     * Adds a divider to the given menu
     *
     * @param ItemInterface $menu
     */
    protected function addDivider(ItemInterface $menu)
    {
        $menu->addChild('divider-' . rand(1, 99999))
            ->setLabel('')
            ->setExtra('divider', true)
            ->setExtra('position', 15); // after manage report, we have 10 there
    }

    /**
     * Get entity menu item for report item
     *
     * @param ItemInterface $reportItem
     * @param string        $entityLabel
     * @return ItemInterface
     */
    protected function getEntityMenuItem(ItemInterface $reportItem, $entityLabel)
    {
        $entityItemName = $entityLabel . '_report_tab';
        $entityItem     = $reportItem->getChild($entityItemName);
        if (!$entityItem) {
            $reportItem->addChild(
                $entityItemName,
                [
                    'label' => $entityLabel,
                    'uri'   => '#',
                    // after divider, all entities will be added in EntityName:ASC order
                    'extras'=> ['position' => 20]
                ]
            );
            $entityItem = $reportItem->getChild($entityItemName);
        }

        return $entityItem;
    }
}
