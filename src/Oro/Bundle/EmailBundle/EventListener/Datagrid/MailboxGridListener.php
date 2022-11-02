<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Filters a mailbox datagrid by organizations the current user has permissions to update
 * if the "email" feature is enabled.
 */
class MailboxGridListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const REDIRECT_DATA_KEY = 'redirectData';

    const PATH_UPDATE_LINK_DIRECT_PARAMS = '[properties][update_link][direct_params]';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    public function onPreBuild(PreBuild $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

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
     */
    public function onBuildAfter(BuildAfter $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

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
            $queryBuilder->andWhere($queryBuilder->expr()->in('m.organization', ':organizations'))
                ->setParameter('organizations', $organizations);
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
        $manager = $this->doctrine->getManagerForClass(Organization::class);

        $qb = $manager->createQueryBuilder();

        $qb->select('o.id')
            ->from(Organization::class, 'o');

        $query = $qb->getQuery();

        $query = $this->aclHelper->apply($query, 'EDIT');

        $result = $query->getArrayResult();
        $result = array_map('current', $result);

        return $result;
    }
}
