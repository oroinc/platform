<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class WorkflowItemRepository extends EntityRepository
{
    /**
     * Get workflow item associated with entity.
     *
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return WorkflowItem|null
     */
    public function findByEntityMetadata($entityClass, $entityIdentifier)
    {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return int
     * @todo: Seems this method in unused now. BAP-2888
     */
    public function checkWorkflowItemsByEntityMetadata($entityClass, $entityIdentifier)
    {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier);
        $qb->select('wi.id');
        $qb->setMaxResults(1);

        return count($qb->getQuery()->getResult()) > 0;
    }

    /**
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return QueryBuilder
     */
    protected function getWorkflowQueryBuilder($entityClass, $entityIdentifier)
    {
        $qb = $this->createQueryBuilder('wi')
            ->innerJoin('wi.definition', 'wd')
            ->where('wd.relatedEntity = :entityClass')
            ->andWhere('wi.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIdentifier);

        return $qb;
    }

    /**
     * Get data for funnel chart
     *
     * @todo: Move this somewhere else from Workflow bundle
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
