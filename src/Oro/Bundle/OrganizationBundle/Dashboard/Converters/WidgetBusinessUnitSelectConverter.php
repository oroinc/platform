<?php

namespace Oro\Bundle\OrganizationBundle\Dashboard\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

/**
 * The dashboard widget configuration converter for select a BusinessUnit entity.
 */
class WidgetBusinessUnitSelectConverter extends WidgetEntitySelectConverter
{
    public function __construct(
        protected OwnerHelper $ownerHelper,
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        $entityClass
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
            ->where(\sprintf('e.%s IN (:ids)', $identityField))
            ->setParameter('ids', $value);

        return $this->aclHelper->apply($qb)->getResult();
    }
}
