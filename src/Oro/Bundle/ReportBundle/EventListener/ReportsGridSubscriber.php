<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ReportsGridSubscriber implements EventSubscriberInterface
{
    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            implode('.', [OrmResultBeforeQuery::NAME, 'reports-grid']) => 'onResultBeforeQuery',
        ];
    }

    /**
     * @param OrmResultBeforeQuery $event
     */
    public function onResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $disabledReports = $this->featureChecker->getDisabledResourcesByType('reports');
        if (!$disabledReports) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('r.internalId'),
                $qb->expr()->notIn('r.internalId', ':reports')
            ))
            ->setParameter('reports', $disabledReports);
    }
}
