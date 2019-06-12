<?php

namespace Oro\Bundle\AttachmentBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Fills parentEntityClass and parentEntityId in File entity after it is persisted or updated.
 */
class SetsParentEntityOnFlushListener
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var \SplObjectStorage */
    private $scheduledForUpdate;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->scheduledForUpdate = new \SplObjectStorage();
    }

    /**
     * Sets parent class name, id and field name for File entities.
     *
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event): void
    {
        $entityManager = $event->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $entities = $unitOfWork->getScheduledEntityUpdates();
        $fileClassMetadata = $entityManager->getClassMetadata(File::class);

        foreach ($entities as $entity) {
            $entityClass = ClassUtils::getRealClass($entity);
            $entityId = $this->getEntityId($entityManager, $entity);

            if (!$entityId) {
                // Entity does not have id.
                continue;
            }

            $this->processEntity(
                $entity,
                $entityManager,
                static function (
                    $entity,
                    string $fieldName,
                    array $files
                ) use (
                    $unitOfWork,
                    $entityClass,
                    $entityId,
                    $fileClassMetadata
                ) {
                    /** @var File $file */
                    foreach ($files as $file) {
                        $file
                            ->setParentEntityClass($entityClass)
                            ->setParentEntityId($entityId)
                            ->setParentEntityFieldName($fieldName);

                        $unitOfWork->recomputeSingleEntityChangeSet($fileClassMetadata, $file);
                    }
                }
            );
        }
    }

    /**
     * @param object $entity
     * @param EntityManager $entityManager
     * @param callable $callback
     */
    private function processEntity($entity, EntityManager $entityManager, callable $callback): void
    {
        $classMetadata = $entityManager->getClassMetadata(ClassUtils::getRealClass($entity));
        if (count($classMetadata->getIdentifier()) !== 1) {
            // Entity does not have id field or it is composite.
            return;
        }

        foreach ($classMetadata->getAssociationMappings() as $mapping) {
            // Skips field if it does not target to File entity.
            if (!$mapping['isOwningSide'] || $mapping['targetEntity'] !== File::class) {
                continue;
            }

            $fileEntities = $this->getFileFieldValue($entity, $mapping['fieldName'], $mapping['type']);
            if (!$fileEntities) {
                continue;
            }

            // Filters only File entities without parent entity class.
            $fileEntities = array_filter($fileEntities, static function (File $file) {
                return !$file->getParentEntityClass();
            });

            // Skips field when no File entities are going to be persisted.
            if (!$fileEntities) {
                continue;
            }

            $callback($entity, $mapping['fieldName'], $fileEntities);
        }
    }

    /**
     * Schedules for update the File entities which should be updated with parent class name, id and field name.
     *
     * @param LifecycleEventArgs $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->processEntity(
            $event->getEntity(),
            $event->getEntityManager(),
            function ($entity, string $fieldName, array $filesEntities) {
                if (!isset($this->scheduledForUpdate[$entity])) {
                    $this->scheduledForUpdate[$entity] = [];
                }

                $fields = $this->scheduledForUpdate[$entity];
                $fields[$fieldName] = $filesEntities;
                $this->scheduledForUpdate[$entity] = $fields;
            }
        );
    }

    /**
     * Applies the scheduled updates of the File entities.
     *
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getEntity();
        if (!$this->scheduledForUpdate->contains($entity)) {
            return;
        }

        $entityManager = $event->getEntityManager();
        $entityId = $this->getEntityId($entityManager, $entity);
        if (!$entityId) {
            throw new \LogicException('The persisted entity does not have an id');
        }

        $entityClass = ClassUtils::getRealClass($entity);
        foreach ($this->scheduledForUpdate[$entity] as $fieldName => $fileEntities) {
            /** @var File $file */
            foreach ($fileEntities as $file) {
                $this->applyExtraUpdates(
                    $entityManager,
                    $file,
                    [
                        'parentEntityClass' => [$file->getParentEntityClass(), $entityClass],
                        'parentEntityId' => [$file->getParentEntityId(), $entityId],
                        'parentEntityFieldName' => [$file->getParentEntityFieldName(), $fieldName],
                    ]
                );
            }
        }

        $this->scheduledForUpdate->detach($entity);
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @param string $associationType
     *
     * @return array
     */
    private function getFileFieldValue($entity, string $fieldName, string $associationType): array
    {
        $value = $this->propertyAccessor->getValue($entity, $fieldName);

        if ($associationType & ClassMetadata::TO_MANY) {
            // Field value is Collection of File entities.
            $value = $value->toArray();
        } else {
            $value = $value ? [$value] : [];
        }

        return $value;
    }

    /**
     * @param EntityManager $entityManager
     * @param object $entity
     *
     * @return mixed|null
     */
    private function getEntityId(EntityManager $entityManager, $entity)
    {
        $classMetadata = $entityManager->getClassMetadata(ClassUtils::getRealClass($entity));
        $identifierFields = $classMetadata->getIdentifier();

        if (count($identifierFields) !== 1) {
            // Entity does not have id field or it is composite.
            return null;
        }

        try {
            return $this->propertyAccessor->getValue($entity, current($identifierFields));
        } catch (NoSuchPropertyException $e) {
            // Id field does not have getter.
            return null;
        }
    }

    /**
     * @param EntityManager $entityManager
     * @param File $file
     * @param array $extraUpdateChangeSet
     */
    private function applyExtraUpdates(EntityManager $entityManager, File $file, array $extraUpdateChangeSet): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();

        foreach ($extraUpdateChangeSet as $changedFieldName => $change) {
            $this->propertyAccessor->setValue($file, $changedFieldName, $change[1]);
            $unitOfWork->propertyChanged($file, $changedFieldName, $change[0], $change[1]);
        }

        $unitOfWork->scheduleExtraUpdate($file, $extraUpdateChangeSet);
        $unitOfWork->recomputeSingleEntityChangeSet($entityManager->getClassMetadata(File::class), $file);
    }
}
