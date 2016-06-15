<?php

namespace Oro\Bundle\DashboardBundle\Provider\Converters;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class WidgetEntitySelectConverter extends ConfigValueConverterAbstract
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityManager */
    protected $entityManager;

    /** @var string */
    protected $entityClass;

    /**
     * @param AclHelper          $aclHelper
     * @param EntityNameResolver $entityNameResolver
     * @param DoctrineHelper     $doctrineHelper
     * @param EntityManager      $entityManager
     * @param string             $entityClass
     */
    public function __construct(
        AclHelper $aclHelper,
        EntityNameResolver $entityNameResolver,
        DoctrineHelper $doctrineHelper,
        EntityManager $entityManager,
        $entityClass
    ) {
        $this->aclHelper          = $aclHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->doctrineHelper     = $doctrineHelper;
        $this->entityManager      = $entityManager;
        $this->entityClass        = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        $entities = $this->getEntities($value);

        $names = [];
        foreach ($entities as $entity) {
            $names[] = $this->entityNameResolver->getName($entity);
        }

        return empty($names) ? null : implode('; ', $names);
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

        $identityField = $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->entityClass);

        $qb = $this->entityManager->getRepository($this->entityClass)->createQueryBuilder('e');
        $qb->where(
            $qb->expr()->in(sprintf('e.%s', $identityField), $value)
        );

        return $this->aclHelper->apply($qb)->getResult();
    }
}
