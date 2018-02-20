<?php

namespace Oro\Bundle\ActivityBundle\EventListener\Datagrid;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

/**
 * This listener add filter for hiding already assigned items for activity entity.
 */
class ContextGridListener
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param EntityClassNameHelper $entityClassNameHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper, EntityClassNameHelper $entityClassNameHelper)
    {
        $this->doctrineHelper        = $doctrineHelper;
        $this->entityClassNameHelper = $entityClassNameHelper;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $dataSource */
        $datagrid = $event->getDatagrid();
        $config = $datagrid->getConfig();

        $targetClass = $config->getExtendedEntityClassName();
        if (!$targetClass) {
            return;
        }

        $parameters   = $datagrid->getParameters();
        $dataSource   = $datagrid->getDatasource();
        $queryBuilder = $dataSource->getQueryBuilder();
        $alias        = current($queryBuilder->getDQLPart('from'))->getAlias();

        if ($dataSource instanceof OrmDatasource &&
            $parameters->has('activityId') &&
            $parameters->has('activityClass')
        ) {
            $id          = $parameters->get('activityId');
            $class       = $parameters->get('activityClass');
            $entityClass = $this->entityClassNameHelper->resolveEntityClass($class, true);

            $entity = $this->doctrineHelper->getEntity($entityClass, $id);

            if ($entity && $entity instanceof ActivityInterface) {
                $targetsArray = $entity->getActivityTargets($targetClass);

                $targetIds = [];
                foreach ($targetsArray as $target) {
                    $targetIds[] = $target->getId();
                }

                if ($targetIds) {
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn(sprintf('%s.id', $alias), $targetIds));
                }
            }
        }
    }
}
