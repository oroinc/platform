<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
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

    /** @var ImapEmailSynchronizer */
    protected $imapSync;

    public function __construct(
        EntityManager $em,
        RequestParameters $requestParameters,
        EmailQueryFactory $factory = null
    ) {
        $this->em      = $em;
        $this->requestParams = $requestParameters;
        $this->queryFactory = $factory;
    }

    public function setEmailSync(ImapEmailSynchronizer $emailSync)
    {
        $this->imapSync = $emailSync;
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

            if ($id = $this->requestParams->get('userId')) {
                $user = $this->em
                    ->getRepository('OroUserBundle:User')
                    ->find($id);

                $origin = $user->getImapConfiguration();
                $originId = $origin !== null ? $origin->getId() : 0;

                $additionalParameters = $this->requestParams->get(RequestParameters::ADDITIONAL_PARAMETERS);

                if ($origin !== null && array_key_exists('refresh', $additionalParameters)) {
                    $this->imapSync->syncOrigins(array($originId));
                }
            } else {
                $originId = 0; // to make sure param bind passed
            }

            $queryBuilder->setParameter('origin_id', $originId);
        }
    }
}
