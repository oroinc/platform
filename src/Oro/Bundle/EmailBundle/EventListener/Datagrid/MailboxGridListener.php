<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class MailboxGridListener
{
    const REDIRECT_DATA_KEY = 'redirectData';

    const PATH_UPDATE_LINK_DIRECT_PARAMS = '[properties][update_link][direct_params]';

    /** @var Registry */
    protected $doctrine;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param Registry  $doctrine
     * @param AclHelper $aclHelper
     */
    public function __construct(Registry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $parameters = $event->getParameters();
        if (!$parameters->has(static::REDIRECT_DATA_KEY)) {
            return;
        }

        $config = $event->getConfig();
        $config->offsetSetByPath(
            static::PATH_UPDATE_LINK_DIRECT_PARAMS,
            array_merge(
                $config->offsetGetByPath(static::PATH_UPDATE_LINK_DIRECT_PARAMS, []),
                [
                    static::REDIRECT_DATA_KEY => $parameters->get(static::REDIRECT_DATA_KEY)
                ]
            )
        );
    }

    /**
     * Mailbox grids have to be manually filtered because mailbox access is determined by access to different entity.
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $ormDataSource */
        $ormDataSource = $event->getDatagrid()->getDatasource();
        $queryBuilder = $ormDataSource->getQueryBuilder();
        $parameters = $event->getDatagrid()->getParameters();

        /*
         * Mailbox grid accepts organization_ids parameters so mailboxes can be filtered by their organization.
         * If no organization is provided, all organizations editable by current users are used.
         */
        if ($parameters->has('organization_ids')) {
            $organizations = $parameters->get('organization_ids');
        } else {
            $organizations = $this->getAuthorisedOrganizationIds();
        }

        if (!empty($organizations)) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('m.organization', $organizations)
            );
        } else {
            $queryBuilder->andWhere('m.organization IS NULL');
        }
    }

    /**
     * Returns a list of organization ids for which, current user has permission to update them.
     *
     * @return array
     */
    protected function getAuthorisedOrganizationIds()
    {
        /** @var EntityManager $manager */
        $manager = $this->doctrine->getManagerForClass('OroOrganizationBundle:Organization');

        $qb = $manager->createQueryBuilder();

        $qb->select('o.id')
            ->from('OroOrganizationBundle:Organization', 'o');

        $query = $qb->getQuery();

        $query = $this->aclHelper->apply($query, 'EDIT');

        $result = $query->getArrayResult();
        $result = array_map('current', $result);

        return $result;
    }
}
