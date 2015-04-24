<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class ContextGridListener
{
    /** @var ObjectManager */
    protected $entityManager;

    /**
     * @param ObjectManager $entityManager
     */
    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $dataSource */
        $datagrid = $event->getDatagrid();
        $config = $datagrid->getConfig();
        $configParameters = $config->toArray();

        if (!array_key_exists('extended_entity_name', $configParameters) ||
            !$configParameters['extended_entity_name']) {
            return;
        }

        $targetClass = $configParameters['extended_entity_name'];
        $parameters = $datagrid->getParameters();
        $dataSource = $datagrid->getDatasource();
        $queryBuilder = $dataSource->getQueryBuilder();
        $alias = current($queryBuilder->getDQLPart('from'))->getAlias();

        if ($dataSource instanceof OrmDatasource && $parameters->has('activityId')) {
            $activityId = $parameters->get('activityId');
            $email = $this->entityManager->getRepository('OroEmailBundle:Email')->find($activityId);

            if ($email) {
                $targetsArray = $email->getActivityTargets($targetClass);

                $targetIds=[];
                foreach ($targetsArray as $target) {
                    $targetIds[]=$target->getId();
                }

                if ($targetIds) {
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$alias.id", $targetIds));
                }
            }
        }
    }
}
