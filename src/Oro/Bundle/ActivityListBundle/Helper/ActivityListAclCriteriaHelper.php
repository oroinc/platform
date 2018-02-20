<?php

namespace Oro\Bundle\ActivityListBundle\Helper;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActivityListAclCriteriaHelper
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param AclHelper                     $aclHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        AclHelper $aclHelper,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->aclHelper = $aclHelper;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Apply additional criteria for query based on provided ACL classes and use standard ACL rules
     * to correct select activity lists
     *
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
        /** @var ActivityListProviderInterface $provider */
        foreach ($providers as $provider) {
            $criteria = $this->getCriteriaByProvider($provider, $mapFields);
            $whereExpression = $criteria->getWhereExpression();
            // ignore Value expression
            if ($whereExpression && !$whereExpression instanceof Value) {
                $aclCriteria->orWhere(Criteria::expr()->orX($whereExpression));
            }
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
        $appliedCriteria = $this->aclHelper->applyAclToCriteria(
            $aclClass,
            $criteria,
            'VIEW',
            $mapFields
        );
        if ($this->authorizationChecker->isGranted('VIEW', 'entity:' . $aclClass)) {
            $appliedCriteria->andWhere(Criteria::expr()->eq('relatedActivityClass', $activityClass));
        }

        return $appliedCriteria;
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
        $qb->addCriteria($criteria);
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
