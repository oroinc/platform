<?php

namespace Oro\Bundle\OrganizationBundle\Dashboard\Converters;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use Oro\Bundle\UserBundle\Dashboard\OwnerHelper;

class WidgetBusinessUnitSelectConverter extends WidgetEntitySelectConverter
{
    /** @var OwnerHelper */
    protected $ownerHelper;

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

        return $this->aclHelper->apply($qb)->getResult();
    }
}
