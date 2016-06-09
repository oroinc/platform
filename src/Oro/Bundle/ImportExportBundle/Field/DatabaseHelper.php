<?php

namespace Oro\Bundle\ImportExportBundle\Field;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class DatabaseHelper
{
    /**
     * @var ManagerRegistry
     *
     * @deprecated since 1.9
     */
    protected $registry;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ServiceLink
     */
    protected $fieldHelperLink;

    /**
     * @var array
     */
    protected $entities = [];

    /**
     * @var ServiceLink
     */
    protected $securityFacadeLink;

    /**
     * @var ServiceLink
     */
    protected $ownershipMetadataProviderLink;

    /**
     * @var array
     */
    protected $organizationLimitsByEntity = [];

    /**
     * @param ManagerRegistry $registry
     * @param DoctrineHelper $doctrineHelper
     * @param ServiceLink $fieldHelperLink
     * @param ServiceLink $securityFacadeLink
     * @param ServiceLink $ownershipMetadataProviderLink
     */
    public function __construct(
        ManagerRegistry $registry,
        DoctrineHelper $doctrineHelper,
        ServiceLink $fieldHelperLink,
        ServiceLink $securityFacadeLink,
        ServiceLink $ownershipMetadataProviderLink
    ) {
        $this->registry = $registry;
        $this->doctrineHelper = $doctrineHelper;
        $this->fieldHelperLink = $fieldHelperLink;
        $this->securityFacadeLink = $securityFacadeLink;
        $this->ownershipMetadataProviderLink = $ownershipMetadataProviderLink;
    }

    /**
     * @param string $entityName
     * @param array $criteria
     * @return object|null
     */
    public function findOneBy($entityName, array $criteria)
    {
        $serializationCriteria = [];
        $where = [];

        foreach ($criteria as $field => $value) {
            if (is_object($value)) {
                $serializationCriteria[$field] = $this->getIdentifier($value);
            } else {
                $serializationCriteria[$field] = $value;
            }
            if (null !== $serializationCriteria[$field]) {
                $where[] = sprintf('e.%s = :%s', $field, $field);
            } else {
                $where[] = sprintf('e.%s IS NULL', $field);
                unset($criteria[$field]);
            }
        }

        $storageKey = $this->getStorageKey($serializationCriteria);

        if (empty($this->entities[$entityName]) || empty($this->entities[$entityName][$storageKey])) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->doctrineHelper->getEntityRepository($entityName);
            $queryBuilder = $entityRepository->createQueryBuilder('e')
                ->andWhere(implode(' AND ', $where))
                ->setParameters($criteria)
                ->setMaxResults(1);

            if ($this->shouldBeAddedOrganizationLimits($entityName)) {
                $ownershipMetadataProvider = $this->ownershipMetadataProviderLink->getService();
                $organizationField = $ownershipMetadataProvider->getMetadata($entityName)->getOrganizationFieldName();
                $queryBuilder->andWhere('e.' . $organizationField . ' = :organization')
                    ->setParameter('organization', $this->securityFacadeLink->getService()->getOrganization());
            }

            $this->entities[$entityName][$storageKey] = $queryBuilder->getQuery()->getOneOrNullResult();
        }

        return $this->entities[$entityName][$storageKey];
    }

    /**
     * @param object $entity
     * @return null|object
     */
    public function findOneByIdentity($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        $identifier = $this->getIdentifier($entity);
        $existingEntity = null;

        // find by identifier
        if ($identifier) {
            $existingEntity = $this->find($entityName, $identifier);
        }

        // find by identity fields
        if (!$existingEntity) {
            /** @var FieldHelper $fieldHelper */
            $fieldHelper = $this->fieldHelperLink->getService();
            $identityValues = $fieldHelper->getIdentityValues($entity);

            // search only by existing identities
            foreach ($identityValues as $value) {
                if (null !== $value && '' !== $value) {
                    $existingEntity = $this->findOneBy($entityName, $identityValues);
                    break;
                }
            }
        }

        return $existingEntity;
    }

    /**
     * @param object $entity
     * @return object
     */
    public function getEntityReference($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        $identifier = $this->getIdentifier($entity);

        return $this->doctrineHelper->getEntityReference($entityName, $identifier);
    }

    /**
     * @param string $entityName
     * @param int|string $identifier
     * @return object|null
     */
    public function find($entityName, $identifier)
    {
        $storageKey = $this->getStorageKey(
            [$this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName) => $identifier]
        );

        if (!empty($this->entities[$entityName][$storageKey])) {
            return $this->entities[$entityName][$storageKey];
        }

        $entity = $this->doctrineHelper->getEntity($entityName, $identifier);

        if ($entity && $this->shouldBeAddedOrganizationLimits($entityName)) {
            $ownershipMetadataProvider = $this->ownershipMetadataProviderLink->getService();
            $organizationField = $ownershipMetadataProvider->getMetadata($entityName)->getOrganizationFieldName();
            /** @var FieldHelper $fieldHelper */
            $fieldHelper = $this->fieldHelperLink->getService();
            $entityOrganization = $fieldHelper->getObjectValue($entity, $organizationField);
            if (!$entityOrganization
                || $entityOrganization->getId() !== $this->securityFacadeLink->getService()->getOrganizationId()
            ) {
                return null;
            }
        }

        $this->entities[$entityName][$storageKey] = $entity;

        return $entity;
    }

    /**
     * @param array $criteria
     * @return string
     */
    protected function getStorageKey(array $criteria = [])
    {
        return serialize($criteria);
    }

    /**
     * @param object $entity
     * @return int|string|null
     */
    public function getIdentifier($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function getIdentifierFieldName($entityName)
    {
        return $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityName);
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    public function isCascadePersist($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);
        return !empty($association['cascade']) && in_array('persist', $association['cascade']);
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    public function getInversedRelationFieldName($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);

        if (!empty($association['mappedBy'])) {
            return $association['mappedBy'];
        }

        if (!empty($association['inversedBy'])) {
            return $association['inversedBy'];
        }

        return null;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    public function isSingleInversedRelation($entityName, $fieldName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);

        return in_array($association['type'], [ClassMetadata::ONE_TO_ONE, ClassMetadata::ONE_TO_MANY]);
    }

    /**
     * @param object $entity
     */
    public function resetIdentifier($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $identifierField = $this->getIdentifierFieldName($entityName);
        $entityManager->getClassMetadata($entityName)->setIdentifierValues($entity, [$identifierField => null]);
    }

    /**
     * Clear cache on doctrine entity manager clear
     */
    public function onClear()
    {
        $this->entities = [];
        $this->organizationLimitsByEntity = [];
    }

    /**
     * We should limit data with current organization
     *
     * @param string $entityName
     * @return bool
     */
    protected function shouldBeAddedOrganizationLimits($entityName)
    {
        if (!array_key_exists($entityName, $this->organizationLimitsByEntity)) {
            $this->organizationLimitsByEntity[$entityName] = $this->securityFacadeLink
                    ->getService()
                    ->getOrganization()
                && $this->ownershipMetadataProviderLink
                    ->getService()
                    ->getMetadata($entityName)
                    ->getOrganizationFieldName();
        }

        return $this->organizationLimitsByEntity[$entityName];
    }

    /**
     * @deprecated since 1.9
     */
    public function getRegistry()
    {
        return $this->registry;
    }
}
