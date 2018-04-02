<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GridsSubscriber implements EventSubscriberInterface
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
            implode('.', [OrmResultBeforeQuery::NAME, 'process-definitions-grid'])  => 'onProcessesResultBeforeQuery',
            implode('.', [OrmResultBeforeQuery::NAME, 'workflow-definitions-grid']) => 'onWorkflowsResultBeforeQuery',
        ];
    }

    /**
     * @param OrmResultBeforeQuery $event
     */
    public function onProcessesResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $disabledProcesses = $this->featureChecker->getDisabledResourcesByType('processes');
        if (!$disabledProcesses) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb
            ->andWhere($qb->expr()->notIn('process.name', ':processes'))
            ->setParameter('processes', $disabledProcesses);
    }

    /**
     * @param OrmResultBeforeQuery $event
     */
    public function onWorkflowsResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $disabledWorkflowEntities = $this->featureChecker->getDisabledResourcesByType('entities');
        if (!$disabledWorkflowEntities) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb
            ->andWhere($qb->expr()->notIn('w.relatedEntity', ':relatedEntities'))
            ->setParameter('relatedEntities', $disabledWorkflowEntities);
    }
}
