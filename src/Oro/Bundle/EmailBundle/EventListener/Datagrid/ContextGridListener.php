<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

class ContextGridListener
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        $targetClass =
            isset($configParameters['extended_entity_name']) ? $configParameters['extended_entity_name'] : null;
        $parameters = $datagrid->getParameters();
        $dataSource = $datagrid->getDatasource();
        $queryBuilder = $dataSource->getQueryBuilder();
        $alias = current($queryBuilder->getDQLPart('from'))->getAlias();

        if ($dataSource instanceof OrmDatasource && $targetClass && $parameters->has('activityId')) {
            $activityId = $parameters->get('activityId');
            $email = $this->doctrine->getRepository('OroEmailBundle:Email')->find($activityId);

            if ($email->getId()) {
                $targetsArray = $email->getActivityTargets($targetClass);

                $targetIds=[];
                foreach ($targetsArray as $target) {
                    $targetIds[]=$target->getId();
                }

                if (count($targetIds) > 0) {
                    $queryBuilder->andWhere($queryBuilder->expr()->notIn("$alias.id", $targetIds));
                }
            }
        }
    }
}
