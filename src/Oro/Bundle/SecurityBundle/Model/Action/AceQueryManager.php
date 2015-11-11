<?php

namespace Oro\Bundle\SecurityBundle\Model\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Composite;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Entity\AclClass;
use Oro\Bundle\SecurityBundle\Form\Model\Share;
use Oro\Bundle\SecurityBundle\Exception\UnknownShareScopeException;

class AceQueryManager implements AceQueryInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoveAceQueryBuilder(AclClass $aclClass, array $removeScopes)
    {
        $qb = $this->doctrineHelper->getEntityRepository('OroSecurityBundle:AclEntry')->createQueryBuilder('ae')
            ->delete();
        $qb->where('ae.class = :class');

        $qbSub = $this->doctrineHelper->getEntityRepository('OroSecurityBundle:AclSecurityIdentity')
            ->createQueryBuilder('asid')->select('asid.id');
        $exprOr = $qbSub->expr()->orX();
        foreach ($removeScopes as $scope) {
            $this->addExprByShareScope($qbSub, $exprOr, $scope);
        }
        $qbSub->where($exprOr);

        $qb->andWhere($qb->expr()->in('ae.securityIdentity', $qbSub->getDQL()))
            ->setParameter('class', $aclClass);

        return $qb;
    }

    /**
     * Add new condition to remove ace by share scope
     *
     * @param QueryBuilder $qb
     * @param Composite    $expr
     * @param string       $scope
     *
     * @throws UnknownShareScopeException
     */
    protected function addExprByShareScope(QueryBuilder $qb, Composite $expr, $scope)
    {
        if ($scope === Share::SHARE_SCOPE_USER) {
            $expr->add($qb->expr()->eq('asid.username', 'true'));
        } elseif ($scope === Share::SHARE_SCOPE_BUSINESS_UNIT) {
            $expr->add(
                $qb->expr()->like(
                    'asid.identifier',
                    $qb->expr()->literal('Oro\\\\Bundle\\\\OrganizationBundle\\\\Entity\\\\BusinessUnit%')
                )
            );
        } else {
            throw new UnknownShareScopeException($scope);
        }
    }
}
