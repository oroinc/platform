<?php

namespace Oro\Bundle\DataAuditBundle\Loggable;

use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata as DoctrineClassMetadata;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAudit;
use Oro\Bundle\DataAuditBundle\Metadata\ClassMetadata;
use Oro\Bundle\DataAuditBundle\Metadata\PropertyMetadata;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * TODO: This class should be refactored  (BAP-978)
 */
class LoggableManager
{
    /**
     * @var AbstractUser[]
     */
    protected static $userCache = [];

    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $configs = [];

    /** @var string */
    protected $username;

    /** @var Organization|null */
    protected $organization;

    /**
     * @deprecated 1.8.0:2.1.0 use AuditEntityMapper::getAuditEntryClass
     *
     * @var string
     */
    protected $logEntityClass;

    /**
     * @deprecated 1.8.0:2.1.0 use AuditEntityMapper::getAuditEntryFieldClass
     *
     * @var string
     */
    protected $logEntityFieldClass;

    /** @var array */
    protected $pendingLogEntityInserts = [];

    /** @var array */
    protected $pendingRelatedEntities = [];

    /** @var array */
    protected $collectionLogData = [];

    /** @var ConfigProvider */
    protected $auditConfigProvider;

    /** @var ServiceLink  */
    protected $securityContextLink;

    /** @var AuditEntityMapper  */
    protected $auditEntityMapper;

    /**
     * @param string $logEntityClass
     * @param string $logEntityFieldClass
     * @param ConfigProvider $auditConfigProvider
     * @param ServiceLink $securityContextLink
     * @param AuditEntityMapper $auditEntityMapper
     */
    public function __construct(
        $logEntityClass,
        $logEntityFieldClass,
        ConfigProvider $auditConfigProvider,
        ServiceLink $securityContextLink,
        AuditEntityMapper $auditEntityMapper
    ) {
        $this->auditConfigProvider = $auditConfigProvider;
        $this->logEntityClass = $logEntityClass;
        $this->logEntityFieldClass = $logEntityFieldClass;
        $this->securityContextLink = $securityContextLink;
        $this->auditEntityMapper = $auditEntityMapper;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public function addConfig(ClassMetadata $metadata)
    {
        $this->configs[$metadata->name] = $metadata;
    }

    /**
     * @param string $name
     * @return ClassMetadata
     * @throws InvalidParameterException
     */
    public function getConfig($name)
    {
        if (!isset($this->configs[$name])) {
            throw new InvalidParameterException(sprintf('invalid config name %s', $name));
        }

        return $this->configs[$name];
    }

    /**
     * @param string $username
     * @throws \InvalidArgumentException
     */
    public function setUsername($username)
    {
        if (is_string($username)) {
            $this->username = $username;
        } elseif (is_object($username) && method_exists($username, 'getUsername')) {
            $this->username = (string) $username->getUsername();
        } else {
            throw new \InvalidArgumentException('Username must be a string, or object should have method: getUsername');
        }
    }

    /**
     * @return null|Organization
     */
    protected function getOrganization()
    {
        /** @var SecurityContextInterface $securityContext */
        $securityContext = $this->securityContextLink->getService();

        $token = $securityContext->getToken();
        if (!$token) {
            return null;
        }

        if (!$token instanceof OrganizationContextTokenInterface) {
            return null;
        }

        return $token->getOrganizationContext();
    }

    /**
     * @param EntityManager $em
     */
    public function handleLoggable(EntityManager $em)
    {
        $this->em = $em;
        $uow      = $em->getUnitOfWork();

        $collections = array_merge($uow->getScheduledCollectionUpdates(), $uow->getScheduledCollectionDeletions());
        foreach ($collections as $collection) {
            $this->calculateActualCollectionData($collection);
        }

        $entities = array_merge(
            $uow->getScheduledEntityDeletions(),
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        $updatedEntities = [];
        foreach ($entities as $entity) {
            $entityMeta      = $this->em->getClassMetadata(ClassUtils::getClass($entity));
            $updatedEntities = array_merge(
                $updatedEntities,
                $this->calculateManyToOneData($entityMeta, $entity)
            );
        }

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->createLogEntity(self::ACTION_CREATE, $entity);
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->createLogEntity(self::ACTION_UPDATE, $entity);
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->createLogEntity(self::ACTION_REMOVE, $entity);
        }

        foreach ($this->collectionLogData as $entityData) {
            foreach ($entityData as $identifier => $values) {
                if (!isset($updatedEntities[$identifier])) {
                    continue;
                }

                $this->createLogEntity(static::ACTION_UPDATE, $updatedEntities[$identifier]);
            }
        }
    }

    /**
     * @param object $entity
     * @param EntityManager $em
     */
    public function handlePostPersist($entity, EntityManager $em)
    {
        $this->em = $em;
        $uow = $em->getUnitOfWork();
        $oid = spl_object_hash($entity);
        $logEntryMeta = null;

        if ($this->pendingLogEntityInserts && array_key_exists($oid, $this->pendingLogEntityInserts)) {
            $logEntry     = $this->pendingLogEntityInserts[$oid];
            $logEntryMeta = $em->getClassMetadata(ClassUtils::getClass($logEntry));

            $id = $this->getIdentifier($entity);
            $logEntryMeta->getReflectionProperty('objectId')->setValue($logEntry, $id);

            $uow->scheduleExtraUpdate($logEntry, ['objectId' => [null, $id]]);
            $uow->setOriginalEntityProperty(spl_object_hash($logEntry), 'objectId', $id);

            unset($this->pendingLogEntityInserts[$oid]);
        }

        if ($this->pendingRelatedEntities && array_key_exists($oid, $this->pendingRelatedEntities)) {
            $identifiers = $uow->getEntityIdentifier($entity);

            foreach ($this->pendingRelatedEntities[$oid] as $props) {
                /** @var AbstractAudit $logEntry */
                $logEntry = $props['log'];
                $data     = $logEntry->getData();
                if (empty($data[$props['field']]['new'])) {
                    $data[$props['field']]['new'] = implode(', ', $identifiers);
                    $oldField = $logEntry->getField($props['field']);
                    $logEntry->createField(
                        $oldField->getField(),
                        $oldField->getDataType(),
                        $data[$props['field']]['new'],
                        $oldField->getOldValue()
                    );

                    if ($logEntryMeta) {
                        $uow->computeChangeSet($logEntryMeta, $logEntry);
                    }
                    $uow->setOriginalEntityProperty(spl_object_hash($logEntry), 'objectId', $data);
                }
            }

            unset($this->pendingRelatedEntities[$oid]);
        }
    }

    /**
     * @param DoctrineClassMetadata $entityMeta
     * @param object                $entity
     *
     * @return array [entityIdentifier => entity, ...]
     */
    protected function calculateManyToOneData(DoctrineClassMetadata $entityMeta, $entity)
    {
        $entities = [];
        foreach ($entityMeta->associationMappings as $assoc) {
            if ($assoc['type'] !== DoctrineClassMetadata::MANY_TO_ONE
                || empty($assoc['inversedBy'])
            ) {
                continue;
            }

            $owner = $entityMeta->getReflectionProperty($assoc['fieldName'])->getValue($entity);
            if (!$owner) {
                continue;
            }

            $ownerMeta = $this->em->getClassMetadata($assoc['targetEntity']);
            $collection = $ownerMeta->getReflectionProperty($assoc['inversedBy'])->getValue($owner);
            if (!$collection instanceof PersistentCollection) {
                continue;
            }

            $entityIdentifier = $this->getEntityIdentifierString($owner);
            $this->calculateActualCollectionData($collection, $entityIdentifier);

            $entities[$entityIdentifier] = $owner;
        }

        return $entities;
    }

    /**
     * @param PersistentCollection $collection
     * @param string $entityIdentifier
     */
    protected function calculateActualCollectionData(PersistentCollection $collection, $entityIdentifier = null)
    {
        $ownerEntity = $collection->getOwner();
        $entityState = $this->em->getUnitOfWork()->getEntityState($ownerEntity, UnitOfWork::STATE_NEW);
        if ($entityState === UnitOfWork::STATE_REMOVED) {
            return;
        }

        $this->calculateCollectionData($collection, $entityIdentifier);
    }

    /**
     * @param PersistentCollection $collection
     * @param string $entityIdentifier
     */
    protected function calculateCollectionData(PersistentCollection $collection, $entityIdentifier = null)
    {
        $ownerEntity          = $collection->getOwner();
        $ownerEntityClassName = $this->getEntityClassName($ownerEntity);

        if ($this->checkAuditable($ownerEntityClassName)) {
            $meta              = $this->getConfig($ownerEntityClassName);
            $collectionMapping = $collection->getMapping();

            if (isset($meta->propertyMetadata[$collectionMapping['fieldName']])) {
                $method = $meta->propertyMetadata[$collectionMapping['fieldName']]->method;

                // calculate collection changes
                $newCollection = $collection->toArray();
                $oldCollection = $collection->getSnapshot();

                $oldCollectionWithOldData = [];
                foreach ($oldCollection as $entity) {
                    $oldCollectionWithOldData[] = $this->getOldEntity($entity);
                }

                $oldData = array_reduce(
                    $oldCollectionWithOldData,
                    function ($result, $item) use ($method) {
                        return $result . ($result ? ', ' : '') . $item->{$method}();
                    }
                );

                $newData = array_reduce(
                    $newCollection,
                    function ($result, $item) use ($method) {
                        return $result . ($result ? ', ' : '') . $item->{$method}();
                    }
                );

                if (!$entityIdentifier) {
                    $entityIdentifier = $this->getEntityIdentifierString($ownerEntity);
                }

                $fieldName = $collectionMapping['fieldName'];
                $this->collectionLogData[$ownerEntityClassName][$entityIdentifier][$fieldName] = [
                    'old' => $oldData,
                    'new' => $newData,
                ];
            }
        }
    }

    /**
     * @param object $currentEntity
     *
     * @return object
     */
    protected function getOldEntity($currentEntity)
    {
        $changeSet = $this->em->getUnitOfWork()->getEntityChangeSet($currentEntity);

        if (!$changeSet) {
            return $currentEntity;
        }

        $metadata = $this->em->getClassMetadata(ClassUtils::getClass($currentEntity));
        $oldEntity = clone $currentEntity;
        foreach ($changeSet as $property => $values) {
            $metadata->getReflectionProperty($property)->setValue($oldEntity, $values[0]);
        }

        return $oldEntity;
    }

    /**
     * @param string $action
     * @param object $entity
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws \ReflectionException
     */
    protected function createLogEntity($action, $entity)
    {
        $entityClassName = $this->getEntityClassName($entity);
        if (!$this->checkAuditable($entityClassName)) {
            return;
        }

        $user = $this->getLoadedUser();
        $organization = $this->getOrganization();
        if (!$organization) {
            return;
        }

        $uow = $this->em->getUnitOfWork();

        $meta       = $this->getConfig($entityClassName);
        $entityMeta = $this->em->getClassMetadata($entityClassName);

        $logEntryMeta = $this->em->getClassMetadata($this->getLogEntityClass());
        /** @var AbstractAudit $logEntry */
        $logEntry = $logEntryMeta->newInstance();
        $logEntry->setAction($action);
        $logEntry->setObjectClass($meta->name);
        $logEntry->setLoggedAt();
        $logEntry->setUser($user);
        $logEntry->setOrganization($organization);
        $logEntry->setObjectName(method_exists($entity, '__toString') ? (string)$entity : $meta->name);

        $entityId = $this->getIdentifier($entity);

        if (!$entityId && $action === self::ACTION_CREATE) {
            $this->pendingLogEntityInserts[spl_object_hash($entity)] = $logEntry;
        }

        $logEntry->setObjectId($entityId);

        $newValues = [];

        if ($action !== self::ACTION_REMOVE && count($meta->propertyMetadata)) {
            foreach ($uow->getEntityChangeSet($entity) as $field => $changes) {
                if (!isset($meta->propertyMetadata[$field])) {
                    continue;
                }

                $old = $changes[0];
                $new = $changes[1];

                if ($old == $new) {
                    continue;
                }

                $fieldMapping = null;
                if ($entityMeta->hasField($field)) {
                    $fieldMapping = $entityMeta->getFieldMapping($field);
                    if ($fieldMapping['type'] === 'date') {
                        // leave only date
                        $utc = new \DateTimeZone('UTC');
                        if ($old && $old instanceof \DateTime) {
                            $old->setTimezone($utc);
                            $old = new \DateTime($old->format('Y-m-d'), $utc);
                        }
                        if ($new && $new instanceof \DateTime) {
                            $new->setTimezone($utc);
                            $new = new \DateTime($new->format('Y-m-d'), $utc);
                        }
                    }
                }

                if ($old instanceof \DateTime && $new instanceof \DateTime
                    && $old->getTimestamp() == $new->getTimestamp()
                ) {
                    continue;
                }

                if ($entityMeta->isSingleValuedAssociation($field) && $new) {
                    $oid   = spl_object_hash($new);
                    $value = $this->getIdentifier($new);

                    if (!is_array($value) && !$value) {
                        $this->pendingRelatedEntities[$oid][] = [
                            'log'   => $logEntry,
                            'field' => $field
                        ];
                    }

                    $method = $meta->propertyMetadata[$field]->method;
                    if ($old !== null) {
                        // check if an object has the required method to avoid a fatal error
                        if (!method_exists($old, $method)) {
                            throw new \ReflectionException(
                                sprintf('Try to call to undefined method %s::%s', get_class($old), $method)
                            );
                        }
                        $old = $old->{$method}();
                    }
                    if ($new !== null) {
                        // check if an object has the required method to avoid a fatal error
                        if (!method_exists($new, $method)) {
                            throw new \ReflectionException(
                                sprintf('Try to call to undefined method %s::%s', get_class($new), $method)
                            );
                        }
                        $new = $new->{$method}();
                    }
                }

                $newValues[$field] = [
                    'old' => $old,
                    'new' => $new,
                    'type' => $this->getFieldType($entityMeta, $field),
                ];
            }

            $entityIdentifier = $this->getEntityIdentifierString($entity);
            if (!empty($this->collectionLogData[$entityClassName][$entityIdentifier])) {
                $collectionData = $this->collectionLogData[$entityClassName][$entityIdentifier];
                foreach ($collectionData as $field => $changes) {
                    if (!isset($meta->propertyMetadata[$field])) {
                        continue;
                    }

                    if ($changes['old'] != $changes['new']) {
                        $newValues[$field] = $changes;
                        $newValues[$field]['type'] = $this->getFieldType($entityMeta, $field);
                    }
                }

                unset($this->collectionLogData[$entityClassName][$entityIdentifier]);
                if (!$this->collectionLogData[$entityClassName]) {
                    unset($this->collectionLogData[$entityClassName]);
                }
            }

            foreach ($newValues as $field => $newValue) {
                $logEntry->createField($field, $newValue['type'], $newValue['new'], $newValue['old']);
            }
        }

        if ($action === self::ACTION_UPDATE && 0 === count($newValues)) {
            return;
        }

        $version = 1;

        if ($action !== self::ACTION_CREATE) {
            $version = $this->getNewVersion($logEntryMeta, $entity);

            if (empty($version)) {
                // was versioned later
                $version = 1;
            }
        }

        $logEntry->setVersion($version);

        $this->em->persist($logEntry);
        $uow->computeChangeSet($logEntryMeta, $logEntry);

        $logEntryFieldMeta = $this->em->getClassMetadata(
            $this->auditEntityMapper->getAuditEntryFieldClass($this->getLoadedUser())
        );
        foreach ($logEntry->getFields() as $field) {
            $this->em->persist($field);
            $uow->computeChangeSet($logEntryFieldMeta, $field);
        }
    }

    /**
     * @return AbstractUser|null
     */
    protected function getLoadedUser()
    {
        if (!$this->username) {
            return null;
        }

        $isInCache = array_key_exists($this->username, self::$userCache);
        if (!$isInCache
            || ($isInCache && !$this->em->getUnitOfWork()->isInIdentityMap(self::$userCache[$this->username]))
        ) {
            /** @var SecurityContextInterface $securityContext */
            $securityContext = $this->securityContextLink->getService();
            $token = $securityContext->getToken();
            if ($token) {
                /** @var AbstractUser $user */
                $user = $token->getUser();
                self::$userCache[$this->username] = $this->em->getReference(
                    ClassUtils::getClass($user),
                    $user->getId()
                );
            }
        }

        return self::$userCache[$this->username];
    }

    /**
     * Get the LogEntry class
     *
     * @return string
     */
    protected function getLogEntityClass()
    {
        return $this->auditEntityMapper->getAuditEntryClass($this->getLoadedUser());
    }

    /**
     * @param DoctrineClassMetadata $logEntityMeta
     * @param object $entity
     * @return mixed
     */
    protected function getNewVersion($logEntityMeta, $entity)
    {
        $entityMeta = $this->em->getClassMetadata($this->getEntityClassName($entity));
        $entityId   = $this->getIdentifier($entity);

        $qb = $this->em->createQueryBuilder();
        $query = $qb
            ->select($qb->expr()->max('log.version'))
            ->from($logEntityMeta->name, 'log')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('log.objectId', ':objectId'),
                    $qb->expr()->eq('log.objectClass', ':objectClass')
                )
            )
            ->setParameter('objectId', $entityId)
            ->setParameter('objectClass', $entityMeta->name)
            ->getQuery();

        return $query->getSingleScalarResult() + 1;
    }

    /**
     * @param  object $entity
     * @param  DoctrineClassMetadata|null $entityMeta
     * @return mixed
     */
    protected function getIdentifier($entity, $entityMeta = null)
    {
        $entityMeta      = $entityMeta ?: $this->em->getClassMetadata($this->getEntityClassName($entity));
        $identifierField = $entityMeta->getSingleIdentifierFieldName();

        return $entityMeta->getReflectionProperty($identifierField)->getValue($entity);
    }

    /**
     * @param string $entityClassName
     * @return bool
     */
    protected function checkAuditable($entityClassName)
    {
        if ($this->auditConfigProvider->hasConfig($entityClassName)
            && $this->auditConfigProvider->getConfig($entityClassName)->is('auditable')
        ) {
            $reflection    = new \ReflectionClass($entityClassName);
            $classMetadata = new ClassMetadata($reflection->getName());

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $fieldName = $reflectionProperty->getName();

                if ($this->auditConfigProvider->hasConfig($entityClassName, $fieldName)
                    && ($fieldConfig = $this->auditConfigProvider->getConfig($entityClassName, $fieldName))
                    && $fieldConfig->is('auditable')
                ) {
                    $propertyMetadata         = new PropertyMetadata($entityClassName, $reflectionProperty->getName());
                    $propertyMetadata->method = '__toString';

                    $classMetadata->addPropertyMetadata($propertyMetadata);
                }
            }

            if (count($classMetadata->propertyMetadata)) {
                $this->addConfig($classMetadata);

                return true;
            }
        }

        return false;
    }

    /**
     * @param object|string $entity
     * @return string
     */
    private function getEntityClassName($entity)
    {
        if (is_object($entity)) {
            return ClassUtils::getClass($entity);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return string
     */
    protected function getEntityIdentifierString($entity)
    {
        $className = $this->getEntityClassName($entity);
        $metadata  = $this->em->getClassMetadata($className);

        return serialize($metadata->getIdentifierValues($entity));
    }

    /**
     * @param DoctrineClassMetadata $entityMeta
     * @param string                $field
     *
     * @return string
     */
    private function getFieldType(DoctrineClassMetadata $entityMeta, $field)
    {
        $type = null;
        if ($entityMeta->hasField($field)) {
            $type = $entityMeta->getTypeOfField($field);
            if ($type instanceof Type) {
                $type = $type->getName();
            }
        } elseif ($entityMeta->hasAssociation($field)) {
            $type = Type::STRING;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not mapped field of "%s" entity.',
                $field,
                $entityMeta->getName()
            ));
        }

        return $type;
    }
}
