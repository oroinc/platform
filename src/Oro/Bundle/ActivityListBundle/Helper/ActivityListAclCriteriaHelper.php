<?php

namespace Oro\Bundle\ActivityListBundle\Helper;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityBundle\ORM\QueryBuilderHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Class ActivityListAclCriteriaHelper
 * @package Oro\Bundle\ActivityListBundle\Helper
 */
class ActivityListAclCriteriaHelper
{
    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param QueryBuilder $qb
     * @param $providers
     */
    public function applyAclCriteria(QueryBuilder $qb, $providers)
    {
        $criteria = $this->getAclCriteria($providers);
        $this->applyCriteriaToQb($qb, $criteria);
    }

    /**
     * @param $providers
     * @return Criteria
     */
    protected function getAclCriteria($providers)
    {
        $aclCriteria = new Criteria();
        $mapFields = $this->getMapFields();
        /**
         * @var ActivityListProviderInterface $provider
         */
        foreach ($providers as $provider) {
            $criteria = $this->getCriteriaByProvider($provider, $mapFields);
            $aclCriteria->orWhere(Criteria::expr()->orX($criteria->getWhereExpression()));
        }
        $this->addDefaultCriteria($aclCriteria);

        return $aclCriteria;
    }

    /**
     * @param ActivityListProviderInterface $provider
     * @param $mapFields
     *
     * @return Criteria
     */
    protected function getCriteriaByProvider(ActivityListProviderInterface $provider, $mapFields)
    {
        $activityClass = $provider->getActivityClass();
        $aclClass = $provider->getAclClass();

        $criteria = new Criteria();
        $criteria = $this->aclHelper->applyAclToCriteria(
            $aclClass,
            $criteria,
            'VIEW',
            $mapFields
        );
        $criteria->andWhere(Criteria::expr()->eq('relatedActivityClass', $activityClass));

        return $criteria;
    }

    /**
     * @param Criteria $criteria
     */
    protected function addDefaultCriteria(Criteria $criteria)
    {
        $defaultCriteria = $this->getCriteriaWithOutOrganizationAndUser();
        $criteria->orWhere(Criteria::expr()->orX($defaultCriteria->getWhereExpression()));
    }

    /**
     * @param QueryBuilder $qb
     * @param Criteria $criteria
     */
    protected function applyCriteriaToQb(QueryBuilder $qb, Criteria $criteria)
    {
        QueryBuilderHelper::addCriteria($qb, $criteria);
    }

    /**
     * @return Criteria
     */
    protected function getCriteriaWithOutOrganizationAndUser()
    {
        $mapField = $this->getMapFields();
        $criteria = new Criteria();
        $criteria->andWhere(Criteria::expr()->eq($mapField['organization'], null));
        $criteria->andWhere(Criteria::expr()->eq($mapField['owner'], null));

        return $criteria;
    }

    /**
     * @return array
     */
    protected function getMapFields()
    {
        return [
            'organization' => 'ao.organization',
            'owner' => 'ao.user'
        ];
    }
}
