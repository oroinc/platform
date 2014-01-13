<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowBindEntity;
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
        $entityIdentifierString = WorkflowBindEntity::convertIdentifiersToString($entityIdentifier);

        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('wi')
            ->from('OroWorkflowBundle:WorkflowItem', 'wi')
            ->innerJoin('wi.bindEntities', 'wbe')
            ->where('wbe.entityClass = :entityClass')
            ->andWhere('wbe.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIdentifierString);

        if ($workflowName) {
            $qb->andWhere('wi.workflowName = :workflowName')
                ->setParameter('workflowName', $workflowName);
        }

        if ($workflowType) {
            $qb->innerJoin('wi.definition', 'wd')
                ->andWhere('wd.type = :workflowType')
                ->setParameter('workflowType', $workflowType);
        }

        return $qb->getQuery()->getResult();
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
        \DateTime $dateEnd= null
    ) {
        $resultData = [];
        $definition = $this->getEntityManager()
            ->getRepository('OroWorkflowBundle:WorkflowDefinition')
            ->findByEntityClass($entityClass);

        if (isset($definition[0])) {
            $workFlow = $definition[0];
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb->select('wi.currentStepName', 'SUM(opp.' . $fieldName .') as budget')
                ->from($entityClass, 'opp')
                ->join('OroWorkflowBundle:WorkflowBindEntity', 'wbe', 'WITH', 'wbe.entityId = opp.id')
                ->join('wbe.workflowItem', 'wi')
                ->andWhere('wi.workflowName = :workFlowName')
                ->setParameter('workFlowName', $workFlow->getName())
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
                    $steps = array_keys($workFlow->getConfiguration()['steps']);
                }
                foreach ($steps as $stepName) {
                    $stepLabel = $workFlow->getConfiguration()['steps'][$stepName]['label'];
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
