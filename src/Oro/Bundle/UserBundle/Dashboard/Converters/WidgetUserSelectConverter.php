<?php

namespace Oro\Bundle\UserBundle\Dashboard\Converters;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

class WidgetUserSelectConverter extends WidgetEntitySelectConverter
{
    /** @var OwnerHelper */
    protected $ownerHelper;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param OwnerHelper        $ownerHelper
     * @param AclHelper          $aclHelper
     * @param EntityNameResolver $entityNameResolver
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityManager      $entityManager
     * @param string             $entityClass
     */
    public function __construct(
        OwnerHelper $ownerHelper,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        EntityManager $entityManager,
        $entityClass
    ) {
        parent::__construct($aclHelper, $entityNameResolver, $doctrineHelper, $entityManager, $entityClass);

        $this->ownerHelper = $ownerHelper;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getEntities($value)
    {
        if (empty($value)) {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $value = array_filter($value);

        $value = $this->ownerHelper->replaceCurrentValues($value);

        $identityField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->entityClass);

        $qb = $this->entityManager->getRepository($this->entityClass)->createQueryBuilder('e');
        $qb->where(
            $qb->expr()->in(sprintf('e.%s', $identityField), $value)
        );

        $qb->leftJoin('e.organizations', 'org')
            ->andWhere('org.id = :org')
            ->setParameter('org', $this->tokenAccessor->getOrganizationId());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }
}
