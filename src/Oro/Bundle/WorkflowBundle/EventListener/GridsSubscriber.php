<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to datagrid query events to filter disabled processes and workflows.
 *
 * This subscriber uses feature toggles to exclude disabled processes and workflows from
 * datagrid results, ensuring that only enabled items are displayed to users.
 */
class GridsSubscriber implements EventSubscriberInterface
{
    /** @var FeatureChecker */
    protected $featureChecker;

    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            implode('.', [OrmResultBeforeQuery::NAME, 'process-definitions-grid'])  => 'onProcessesResultBeforeQuery',
            implode('.', [OrmResultBeforeQuery::NAME, 'workflow-definitions-grid']) => 'onWorkflowsResultBeforeQuery',
        ];
    }

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
