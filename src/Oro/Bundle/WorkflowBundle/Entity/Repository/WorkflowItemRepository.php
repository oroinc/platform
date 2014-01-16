<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class WorkflowItemRepository extends EntityRepository
{
    /**
     * Get workflow items associated with entity.
     *
     * @param string $entityClass
     * @param string|array $entityIdentifier
     * @param string|null $workflowName
     * @param string|null $workflowType
     * @return array
     */
    public function findByEntityMetadata($entityClass, $entityIdentifier, $workflowName = null, $workflowType = null)
    {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier, $workflowName, $workflowType);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $entityClass
     * @param string|array $entityIdentifier
     * @param null|string $workflowName
     * @param null|string $workflowType
     * @param null|string $skippedWorkflow
     * @return int
     */
    public function checkWorkflowItemsByEntityMetadata(
        $entityClass,
        $entityIdentifier,
        $workflowName = null,
        $workflowType = null,
        $skippedWorkflow = null
    ) {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier, $workflowName, $workflowType);
        $qb->select('wi.id');

        if ($skippedWorkflow) {
            $qb->andWhere('wi.workflowName != :skippedWorkflowName')
                ->setParameter('skippedWorkflowName', $skippedWorkflow);
        }
        $qb->setMaxResults(1);

        return count($qb->getQuery()->getResult()) > 0;
    }

    /**
     * @param string $entityClass
     * @param string|array $entityIdentifier
     * @param null|string $workflowName
     * @param null|string $workflowType
     * @internal param null|string $skippedWorkflow
     * @return QueryBuilder
     */
    protected function getWorkflowQueryBuilder(
        $entityClass,
        $entityIdentifier,
        $workflowName = null,
        $workflowType = null
    ) {
        //TODO: remove bindEntities usage in BAP-2888
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('wi')
            ->from('OroWorkflowBundle:WorkflowItem', 'wi')
            ->innerJoin('wi.bindEntities', 'wbe')
            ->where('wbe.entityClass = :entityClass')
            ->andWhere('wbe.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIdentifier);

        if ($workflowName) {
            $qb->andWhere('wi.workflowName = :workflowName')
                ->setParameter('workflowName', $workflowName);
        }

        if ($workflowType) {
            $qb->innerJoin('wi.definition', 'wd')
                ->andWhere('wd.type = :workflowType')
                ->setParameter('workflowType', $workflowType);
        }

        return $qb;
    }

    /**
     * Get data for funnel chart
     *
     * @param $entityClass
     * @param $fieldName
     * @param array $visibleSteps
     * @param AclHelper $aclHelper
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     * @return array
     */
    public function getFunnelChartData(
        $entityClass,
        $fieldName,
        $visibleSteps = [],
        AclHelper $aclHelper = null,
        \DateTime $dateStart = null,
        \DateTime $dateEnd = null
    ) {
        $resultData = [];
        $workflow = $this->getEntityManager()
            ->getRepository('OroWorkflowBundle:WorkflowDefinition')
            ->findByEntityClass($entityClass);

        if ($workflow) {
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->select('wi.currentStepName', 'SUM(opp.' . $fieldName .') as budget')
                ->from($entityClass, 'opp')
                ->join(
                    'OroWorkflowBundle:WorkflowBindEntity',
                    'wbe',
                    'WITH',
                    'wbe.entityId = opp.id and wbe.entityClass = :entityClass'
                )
                ->join('wbe.workflowItem', 'wi')
                ->andWhere('wi.workflowName = :workFlowName')
                ->setParameter('entityClass', $entityClass)
                ->setParameter('workFlowName', $workflow->getName())
                ->groupBy('wi.currentStepName');

            if ($dateStart && $dateEnd) {
                $qb->andWhere($qb->expr()->between('opp.createdAt', ':dateFrom', ':dateTo'))
                    ->setParameter('dateFrom', $dateStart)
                    ->setParameter('dateTo', $dateEnd);
            }

            if ($aclHelper) {
                $query = $aclHelper->apply($qb);
            } else {
                $query = $qb->getQuery();
            }
            $data = $query->getArrayResult();

            if (!empty($data) || !empty($visibleSteps)) {
                if (!empty($visibleSteps)) {
                    $steps = $visibleSteps;
                } else {
                    $steps = array_keys($workflow->getConfiguration()['steps']);
                }
                foreach ($steps as $stepName) {
                    $stepLabel = $workflow->getConfiguration()['steps'][$stepName]['label'];
                    foreach ($data as $dataValue) {
                        if ($dataValue['currentStepName'] == $stepName) {
                            $resultData[$stepLabel] = (double)$dataValue['budget'];
                        }
                    }

                    if (!isset($resultData[$stepLabel] )) {
                        $resultData[$stepLabel] = 0;
                    }
                }
            }
        }

        return $resultData;
    }
}
