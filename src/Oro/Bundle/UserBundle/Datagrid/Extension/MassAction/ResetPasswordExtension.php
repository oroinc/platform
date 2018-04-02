<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Provider\DatagridModeProvider;
use Symfony\Component\HttpFoundation\RequestStack;

class ResetPasswordExtension extends AbstractExtension
{
    const USERS_GRID_NAME = 'users-grid';

    /** @var RequestStack */
    protected $requestStack;

    /** {@inheritdoc} */
    protected $excludedModes = [
        DatagridModeProvider::DATAGRID_IMPORTEXPORT_MODE
    ];

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $request = $this->requestStack->getCurrentRequest();
        $name = $config->offsetGetOr('name', null);

        return
            parent::isApplicable($config)
            && $name
            && $name === self::USERS_GRID_NAME
            && $request
            && $request->get('actionName', false) === 'reset_password';
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if ($datasource instanceof OrmDatasource) {
            /** @var QueryBuilder $qb */
            $qb = $datasource->getQueryBuilder();
            $qb->select('u');

            $datasource->setQueryBuilder($qb);
        }
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
    }
}
