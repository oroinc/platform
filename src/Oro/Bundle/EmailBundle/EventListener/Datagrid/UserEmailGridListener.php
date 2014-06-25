<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\EmailBundle\Sync\EmailSynchronizationManager;

class UserEmailGridListener
{
    /** @var  EntityManager */
    protected $em;

    /** @var  EmailQueryFactory */
    protected $queryFactory;

    /** @var EmailSynchronizationManager */
    protected $emailSyncManager;

    public function __construct(
        EntityManager $em,
        EmailSynchronizationManager $emailSyncManager,
        EmailQueryFactory $factory = null
    ) {
        $this->em               = $em;
        $this->emailSyncManager = $emailSyncManager;
        $this->queryFactory     = $factory;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        if ($event->getDatagrid()->getName() != 'user-email-grid') {
            return;
        }

        // Remove twig column configuration - field should be rendered like plain text
        // TODO: fix datagrid yaml definition merge in order to make possible override column twig template
        // or unset some keys from parent grid definition
        $config = $event->getConfig();
        $config->offsetUnsetByPath('[columns][subject][type]');
        $config->offsetUnsetByPath('[columns][subject][frontend_type]');
        $config->offsetUnsetByPath('[columns][subject][template]');
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();
        if ($datasource instanceof OrmDatasource) {
            $parameters = $datagrid->getParameters();
            $queryBuilder = $datasource->getQueryBuilder();

            if ($this->queryFactory !== null) {
                $this->queryFactory->prepareQuery($queryBuilder);
            }

            $originIds = []; // to make sure param bind passed
            $userId = $parameters->get('userId');
            if ($userId) {
                $user = $this->em
                    ->getRepository('OroUserBundle:User')
                    ->find($userId);

                $emailOrigins = $user->getEmailOrigins();
                foreach ($emailOrigins as $emailOrigin) {
                    $originIds[] = $emailOrigin->getId();
                }

                $additionalParameters = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS);
                if (!empty($originIds)
                    && !empty($additionalParameters)
                    && array_key_exists('refresh', $additionalParameters)
                ) {
                    $this->emailSyncManager->syncOrigins($emailOrigins);
                }
            }

            $queryBuilder->setParameter('origin_ids', $originIds);
        }
    }
}
