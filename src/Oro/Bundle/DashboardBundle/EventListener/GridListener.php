<?php

namespace Oro\Bundle\DashboardBundle\EventListener;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

class GridListener
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * Add required filters
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $ormDataSource */
        $ormDataSource = $event->getDatagrid()->getDatasource();
        $queryBuilder  = $ormDataSource->getQueryBuilder();
        $parameters    = $event->getDatagrid()->getParameters();

        $queryBuilder->setParameter('organization', $this->securityContext->getToken()->getOrganizationContext());
    }
}
