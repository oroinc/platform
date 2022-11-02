<?php

namespace Oro\Bundle\ApiBundle\Form\Handler;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityIdMetadataAdapter;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Form\ReflectionUtil;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Handles "update", "add" and "delete" operations for forms that have unidirectional associations.
 */
class UnidirectionalAssociationHandler
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /** @var EntityIdHelper */
    private $entityIdHelper;

    /** @var EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        PropertyAccessorInterface $propertyAccessor,
        EntityIdHelper $entityIdHelper,
        EntityOverrideProviderRegistry $entityOverrideProviderRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->propertyAccessor = $propertyAccessor;
        $this->entityIdHelper = $entityIdHelper;
        $this->entityOverrideProviderRegistry = $entityOverrideProviderRegistry;
    }

    /**
     * Handles "update" operation.
     *
     * @param FormInterface          $form
     * @param EntityDefinitionConfig $config
     * @param array                  $unidirectionalAssociations [field name => target association name, ...]
     * @param RequestType            $requestType
     */
    public function handleUpdate(
        FormInterface $form,
        EntityDefinitionConfig $config,
        array $unidirectionalAssociations,
        RequestType $requestType
    ): void {
        $entity = $form->getData();
        $metadata = new EntityIdMetadataAdapter($this->doctrineHelper->getClass($entity), $config);
        foreach ($unidirectionalAssociations as $fieldName => $targetAssociationName) {
            $fieldForm = $form->get($fieldName);
            if (!FormUtil::isSubmittedAndValid($fieldForm)) {
                continue;
            }

            $field = $config->getField($fieldName);
            $previousTargetEntities = $this->getPreviousTargetEntities(
                $entity,
                $field->getAssociationQuery(),
                $metadata
            );
            $targetEntityClass = $field->getTargetClass();
            $targetEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass(
                $this->getEntityClass($targetEntityClass, $requestType)
            );
            if ($targetEntityMetadata->isCollectionValuedAssociation($targetAssociationName)) {
                $this->updateCollectionValuedAssociation(
                    $entity,
                    $previousTargetEntities,
                    $fieldForm,
                    $targetEntityClass,
                    $targetAssociationName
                );
            } else {
                $this->updateSingleValuedAssociation(
                    $entity,
                    $previousTargetEntities,
                    $fieldForm,
                    $targetAssociationName
                );
            }
        }
    }

    /**
     * Handles "add" operation.
     *
     * @param FormInterface          $form
     * @param EntityDefinitionConfig $config
     * @param array                  $unidirectionalAssociations [field name => target association name, ...]
     * @param RequestType            $requestType
     */
    public function handleAdd(
        FormInterface $form,
        EntityDefinitionConfig $config,
        array $unidirectionalAssociations,
        RequestType $requestType
    ): void {
        $entity = $form->getData();
        foreach ($unidirectionalAssociations as $fieldName => $targetAssociationName) {
            $fieldForm = $form->get($fieldName);
            if (!FormUtil::isSubmittedAndValid($fieldForm)) {
                continue;
            }

            $targetEntityClass = $config->getField($fieldName)->getTargetClass();
            $targetEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass(
                $this->getEntityClass($targetEntityClass, $requestType)
            );
            if ($targetEntityMetadata->isCollectionValuedAssociation($targetAssociationName)) {
                $this->addToCollectionValuedAssociation(
                    $entity,
                    $fieldForm,
                    $targetEntityClass,
                    $targetAssociationName
                );
            } else {
                $this->addToSingleValuedAssociation(
                    $entity,
                    $fieldForm,
                    $targetAssociationName
                );
            }
        }
    }

    /**
     * Handles "delete" operation.
     *
     * @param FormInterface          $form
     * @param EntityDefinitionConfig $config
     * @param array                  $unidirectionalAssociations [field name => target association name, ...]
     * @param RequestType            $requestType
     */
    public function handleDelete(
        FormInterface $form,
        EntityDefinitionConfig $config,
        array $unidirectionalAssociations,
        RequestType $requestType
    ): void {
        $entity = $form->getData();
        $metadata = new EntityIdMetadataAdapter($this->doctrineHelper->getClass($entity), $config);
        foreach ($unidirectionalAssociations as $fieldName => $targetAssociationName) {
            $fieldForm = $form->get($fieldName);
            if (!FormUtil::isSubmittedAndValid($fieldForm)) {
                continue;
            }

            $field = $config->getField($fieldName);
            $previousTargetEntities = $this->getPreviousTargetEntities(
                $entity,
                $field->getAssociationQuery(),
                $metadata
            );
            $targetEntityClass = $field->getTargetClass();
            $targetEntityMetadata = $this->doctrineHelper->getEntityMetadataForClass(
                $this->getEntityClass($targetEntityClass, $requestType)
            );
            if ($targetEntityMetadata->isCollectionValuedAssociation($targetAssociationName)) {
                $this->deleteFromCollectionValuedAssociation(
                    $entity,
                    $previousTargetEntities,
                    $fieldForm,
                    $targetEntityClass,
                    $targetAssociationName
                );
            } else {
                $this->deleteFromSingleValuedAssociation(
                    $previousTargetEntities,
                    $fieldForm,
                    $targetAssociationName
                );
            }
        }
    }

    /**
     * @param object        $entity
     * @param array         $previousTargetEntities
     * @param FormInterface $fieldForm
     * @param string        $targetEntityClass
     * @param string        $targetAssociationName
     */
    private function updateCollectionValuedAssociation(
        $entity,
        array $previousTargetEntities,
        FormInterface $fieldForm,
        string $targetEntityClass,
        string $targetAssociationName
    ): void {
        [$targetAssociationAdder, $targetAssociationRemover] = $this->getAdderAndRemover(
            $targetEntityClass,
            $targetAssociationName
        );
        $newTargetEntities = $fieldForm->getData();
        foreach ($newTargetEntities as $targetEntity) {
            $currentEntities = $this->propertyAccessor->getValue($targetEntity, $targetAssociationName);
            if (!$this->hasEntity($entity, $currentEntities)) {
                $targetEntity->{$targetAssociationAdder}($entity);
            }
        }
        foreach ($previousTargetEntities as $targetEntity) {
            if (!$this->hasEntity($targetEntity, $newTargetEntities)) {
                $targetEntity->{$targetAssociationRemover}($entity);
            }
        }
    }

    /**
     * @param object        $entity
     * @param array         $previousTargetEntities
     * @param FormInterface $fieldForm
     * @param string        $targetAssociationName
     */
    private function updateSingleValuedAssociation(
        $entity,
        array $previousTargetEntities,
        FormInterface $fieldForm,
        string $targetAssociationName
    ): void {
        $newTargetEntities = $fieldForm->getData();
        foreach ($newTargetEntities as $targetEntity) {
            $currentEntity = $this->propertyAccessor->getValue($targetEntity, $targetAssociationName);
            if ($entity !== $currentEntity) {
                $this->propertyAccessor->setValue($targetEntity, $targetAssociationName, $entity);
            }
        }
        foreach ($previousTargetEntities as $targetEntity) {
            if (!$this->hasEntity($targetEntity, $newTargetEntities)) {
                $this->propertyAccessor->setValue($targetEntity, $targetAssociationName, null);
            }
        }
    }

    /**
     * @param object        $entity
     * @param FormInterface $fieldForm
     * @param string        $targetEntityClass
     * @param string        $targetAssociationName
     */
    private function addToCollectionValuedAssociation(
        $entity,
        FormInterface $fieldForm,
        string $targetEntityClass,
        string $targetAssociationName
    ): void {
        $targetAssociationAdder = $this->getAdder($targetEntityClass, $targetAssociationName);
        $targetEntities = $fieldForm->getData();
        foreach ($targetEntities as $targetEntity) {
            $currentEntities = $this->propertyAccessor->getValue($targetEntity, $targetAssociationName);
            if (!$this->hasEntity($entity, $currentEntities)) {
                $targetEntity->{$targetAssociationAdder}($entity);
            }
        }
    }

    /**
     * @param object        $entity
     * @param FormInterface $fieldForm
     * @param string        $targetAssociationName
     */
    private function addToSingleValuedAssociation(
        $entity,
        FormInterface $fieldForm,
        string $targetAssociationName
    ): void {
        $targetEntities = $fieldForm->getData();
        foreach ($targetEntities as $targetEntity) {
            $currentEntity = $this->propertyAccessor->getValue($targetEntity, $targetAssociationName);
            if ($entity !== $currentEntity) {
                $this->propertyAccessor->setValue($targetEntity, $targetAssociationName, $entity);
            }
        }
    }

    /**
     * @param object        $entity
     * @param array         $previousTargetEntities
     * @param FormInterface $fieldForm
     * @param string        $targetEntityClass
     * @param string        $targetAssociationName
     */
    private function deleteFromCollectionValuedAssociation(
        $entity,
        array $previousTargetEntities,
        FormInterface $fieldForm,
        string $targetEntityClass,
        string $targetAssociationName
    ): void {
        $targetAssociationRemover = $this->getRemover($targetEntityClass, $targetAssociationName);
        $targetEntities = $fieldForm->getData();
        foreach ($targetEntities as $targetEntity) {
            if ($this->hasEntity($targetEntity, $previousTargetEntities)) {
                $targetEntity->{$targetAssociationRemover}($entity);
            }
        }
    }

    private function deleteFromSingleValuedAssociation(
        array $previousTargetEntities,
        FormInterface $fieldForm,
        string $targetAssociationName
    ): void {
        $targetEntities = $fieldForm->getData();
        foreach ($targetEntities as $targetEntity) {
            if ($this->hasEntity($targetEntity, $previousTargetEntities)) {
                $this->propertyAccessor->setValue($targetEntity, $targetAssociationName, null);
            }
        }
    }

    /**
     * @param object                    $entity
     * @param QueryBuilder              $associationQuery
     * @param EntityIdMetadataInterface $metadata
     *
     * @return object[]
     */
    private function getPreviousTargetEntities(
        $entity,
        QueryBuilder $associationQuery,
        EntityIdMetadataInterface $metadata
    ): array {
        $entityId = $this->entityIdHelper->getEntityIdentifier($entity, $metadata);
        if ($this->entityIdHelper->isEntityIdentifierEmpty($entityId)) {
            return [];
        }

        $qb = clone $associationQuery;
        $qb->select('r');
        $this->entityIdHelper->applyEntityIdentifierRestriction($qb, $entityId, $metadata);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param object            $entity
     * @param iterable|object[] $entities
     *
     * @return bool
     */
    private function hasEntity($entity, $entities): bool
    {
        foreach ($entities as $e) {
            if ($entity === $e) {
                return true;
            }
        }

        return false;
    }

    private function getEntityClass(string $class, RequestType $requestType): string
    {
        $entityClass = $this->entityOverrideProviderRegistry
            ->getEntityOverrideProvider($requestType)
            ->getEntityClass($class);
        if ($entityClass) {
            return $entityClass;
        }

        return $class;
    }

    /**
     * @param string $entityClass
     * @param string $associationName
     *
     * @return array [adder, remover]
     */
    private function getAdderAndRemover(string $entityClass, string $associationName): array
    {
        $methods = ReflectionUtil::findAdderAndRemover($entityClass, $associationName);
        if (!$methods) {
            throw new \RuntimeException(sprintf(
                'The class "%s" must have adder and remover methods for the association "%s".',
                $entityClass,
                $associationName
            ));
        }

        return $methods;
    }

    private function getAdder(string $entityClass, string $associationName): string
    {
        $methods = $this->getAdderAndRemover($entityClass, $associationName);

        return $methods[0];
    }

    private function getRemover(string $entityClass, string $associationName): string
    {
        $methods = $this->getAdderAndRemover($entityClass, $associationName);

        return $methods[1];
    }
}
