<?php

namespace Oro\Bundle\SegmentBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class SegmentsGridSubscriber implements EventSubscriberInterface
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
            implode('.', [OrmResultBeforeQuery::NAME, 'oro_segments-grid']) => 'onResultBeforeQuery',
        ];
    }

    /**
     * @param OrmResultBeforeQuery $event
     */
    public function onResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $disabledSegments = $this->featureChecker->getDisabledResourcesByType('segments');
        if (!$disabledSegments) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('s.internalId'),
                $qb->expr()->notIn('s.internalId', ':segments')
            ))
            ->setParameter('segments', $disabledSegments);
    }
}
