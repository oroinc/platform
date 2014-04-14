<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class UserEmailGridListener
{
    /** @var  EntityManager */
    protected $em;

    /** @var RequestParameters */
    protected $requestParams;

    /** @var  EmailQueryFactory */
    protected $queryFactory;

    /** @var EmailSynchronizationManager */
    protected $emailSyncManager;

    public function __construct(
        EntityManager $em,
        RequestParameters $requestParameters,
        EmailSynchronizationManager $emailSyncManager,
        EmailQueryFactory $factory = null
    ) {
        $this->em      = $em;
        $this->requestParams = $requestParameters;
        $this->emailSyncManager = $emailSyncManager;
        $this->queryFactory = $factory;
    }

    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            /** @var QueryBuilder $query */
            $queryBuilder = $datasource->getQueryBuilder();

            if ($this->queryFactory !== null) {
                $this->queryFactory->prepareQuery($queryBuilder);
            }

            $originIds = []; // to make sure param bind passed
            if ($id = $this->requestParams->get('userId')) {
                $user = $this->em
                    ->getRepository('OroUserBundle:User')
                    ->find($id);

                $emailOrigins = $user->getEmailOrigins();
                if ($emailOrigins->count()) {
                    foreach ($emailOrigins as $emailOrigin) {
                        $originIds[] = $emailOrigin->getId();
                    }
                }

                $additionalParameters = $this->requestParams->get(RequestParameters::ADDITIONAL_PARAMETERS);

                if (count($emailOrigins) && array_key_exists('refresh', $additionalParameters)) {
                    $this->emailSyncManager->syncOrigins($emailOrigins);
                }
            }

            $queryBuilder->setParameter('origin_ids', $originIds);
        }
    }
}
