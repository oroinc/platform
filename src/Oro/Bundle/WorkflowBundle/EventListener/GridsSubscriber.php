<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

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
        $disabledWorkflows = $this->featureChecker->getDisabledResourcesByType('workflows');
        if (!$disabledWorkflows) {
            return;
        }

        $qb = $event->getQueryBuilder();
        $qb
            ->andWhere($qb->expr()->notIn('w.name', ':workflows'))
            ->setParameter('workflows', $disabledWorkflows);
    }
}
