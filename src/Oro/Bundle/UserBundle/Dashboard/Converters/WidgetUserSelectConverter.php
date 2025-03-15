<?php

namespace Oro\Bundle\UserBundle\Dashboard\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

/**
 * The dashboard widget configuration converter for select User entity.
 */
class WidgetUserSelectConverter extends WidgetEntitySelectConverter
{
    public function __construct(
        protected OwnerHelper $ownerHelper,
        protected TokenAccessorInterface $tokenAccessor,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        string $entityClass
    ) {
        parent::__construct($aclHelper, $entityNameResolver, $doctrineHelper, $entityClass);
    }

    #[\Override]
    protected function getEntities(mixed $value): mixed
    {
        if (empty($value)) {
            return [];
        }

        if (!\is_array($value)) {
            $value = [$value];
        }

        $value = array_filter($value);

        $value = $this->ownerHelper->replaceCurrentValues($value);

        $identityField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->entityClass);

        $qb = $this->doctrineHelper->createQueryBuilder($this->entityClass, 'e')
            ->leftJoin('e.organizations', 'org')
            ->where(\sprintf('e.%s IN (:ids)', $identityField))
            ->andWhere('org.id = :org')
            ->setParameter('ids', $value)
            ->setParameter('org', $this->tokenAccessor->getOrganizationId());

        return $qb->getQuery()->getResult();
    }
}
