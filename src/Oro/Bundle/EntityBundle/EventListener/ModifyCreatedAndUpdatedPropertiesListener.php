<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\EntityProperty\CreatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedAtAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\UpdatedByAwareInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Update createdAt/updatedAt for entities
 */
class ModifyCreatedAndUpdatedPropertiesListener
{
    use OptionalListenerTrait;

    protected TokenStorageInterface $tokenStorage;
    private ConfigManager $configManager;
    private array $processedOwningEntities = [];

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ConfigManager $configManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->configManager = $configManager;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $isCreatedAtUpdated = $this->updateCreatedAt($entity);
            $isUpdatedPropertiesUpdated = $this->setUpdatedProperties($entity);
            if ($isCreatedAtUpdated || $isUpdatedPropertiesUpdated) {
                $this->updateChangeSets($entity, $em, $uow);
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->updateOwningEntityForCollectionItem($entity, $em, $uow);
            if ($this->setUpdatedProperties($entity)) {
                $this->updateChangeSets($entity, $em, $uow);
            }
        }
        $this->processedOwningEntities = [];
    }

    /**
     * Updates collection owner updateAt when any of its children one-to-many was updated
     */
    protected function updateOwningEntityForCollectionItem($entity, EntityManager $em, UnitOfWork $uow)
    {
        $metadata = $em->getClassMetadata(ClassUtils::getClass($entity));
        foreach ($metadata->getAssociationMappings() as $associationMapping) {
            if ($associationMapping['type'] === ClassMetadata::MANY_TO_ONE
                && !empty($associationMapping['inversedBy'])
            ) {
                $ownerClass = $associationMapping['targetEntity'];
                $collectionField = $associationMapping['inversedBy'];
                if (!$this->isOwningEntityActualizationEnabled($ownerClass, $collectionField)) {
                    continue;
                }
                $owningEntity = $metadata->getFieldValue($entity, $associationMapping['fieldName']);
                if (!$owningEntity) {
                    continue;
                }

                // Skip already processed owning entity
                $entityKey = $this->getEntityKey($ownerClass, $owningEntity);
                if (!empty($this->processedOwningEntities[$entityKey])) {
                    continue;
                }

                if ($this->setUpdatedProperties($owningEntity)) {
                    $this->updateChangeSets($owningEntity, $em, $uow);
                }
                $this->processedOwningEntities[$entityKey] = true;
            }
        }
    }

    private function isOwningEntityActualizationEnabled(string $entityClass, string $collectionField): bool
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }
        $fieldConfig = $this->configManager
            ->getFieldConfig('entity', $entityClass, $collectionField);
        if (!$fieldConfig->get('actualize_owning_side_on_change')) {
            return false;
        }

        return true;
    }

    /**
     * @param object $entity
     * @param EntityManager $em
     * @param UnitOfWork $uow
     */
    protected function updateChangeSets($entity, EntityManager $em, UnitOfWork $uow)
    {
        $uow->recomputeSingleEntityChangeSet(
            $em->getClassMetadata(ClassUtils::getClass($entity)),
            $entity
        );
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function setUpdatedProperties($entity)
    {
        $isUpdatedAtUpdated = $this->updateUpdatedAt($entity);
        $isUpdatedByUpdated = $this->updateUpdatedBy($entity);

        return $isUpdatedAtUpdated || $isUpdatedByUpdated;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function updateCreatedAt($entity)
    {
        if ($entity instanceof CreatedAtAwareInterface && !$entity->getCreatedAt()) {
            $entity->setCreatedAt($this->getNowDate());

            return true;
        }

        return false;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function updateUpdatedAt($entity)
    {
        if ($entity instanceof UpdatedAtAwareInterface && !$entity->isUpdatedAtSet()) {
            $entity->setUpdatedAt($this->getNowDate());

            return true;
        }

        return false;
    }

    /**
     * @param object $entity
     *
     * @return bool
     */
    protected function updateUpdatedBy($entity)
    {
        if ($entity instanceof UpdatedByAwareInterface && !$entity->isUpdatedBySet()) {
            $user = $this->getUser();
            if (null !== $user) {
                $entity->setUpdatedBy($user);

                return true;
            }
        }

        return false;
    }

    /**
     * @return User|null
     */
    protected function getUser()
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @return \DateTime
     */
    protected function getNowDate()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    private function getEntityKey(string $entityClass, object $entity): string
    {
        if (method_exists($entity, 'getId')) {
            $idKey = $entity->getId();
        } else {
            $idKey = spl_object_hash($entity);
        }

        return $entityClass . ':' . $idKey;
    }
}
