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
     * @param ManagerRegistry           $registry
     * @param DoctrineHelper            $doctrineHelper
     * @param ServiceLink               $fieldHelperLink
     * @param ServiceLink            $securityFacadeLink
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
            $where[] = sprintf('e.%s = :%s', $field, $field);
        }

        $storageKey = serialize($serializationCriteria);

        if (empty($this->entities[$entityName]) || !array_key_exists($storageKey, $this->entities[$entityName])) {
            /** @var EntityRepository $entityRepository */
            $entityRepository = $this->registry->getRepository($entityName);
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

        return $entity;
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
        $entityManager = $this->registry->getManagerForClass($entityName);
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
        $entityManager = $this->registry->getManagerForClass($entityName);
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
        $entityManager = $this->registry->getManagerForClass($entityName);
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
        $entityManager = $this->registry->getManagerForClass($entityName);
        $identifierField = $this->getIdentifierFieldName($entityName);
        $entityManager->getClassMetadata($entityName)->setIdentifierValues($entity, [$identifierField => null]);
    }

    /**
     * Clear cache on doctrine entity manager clear
     */
    public function onClear()
    {
        $this->entities = [];
    }

    /**
     * We should limit data with current organization
     *
     * @param $entityName
     * @return bool
     */
    protected function shouldBeAddedOrganizationLimits($entityName)
    {
        return $this->securityFacadeLink->getService()->getOrganization()
            && $this->ownershipMetadataProviderLink->getService()->getMetadata($entityName)->getOrganizationFieldName();
    }
}
