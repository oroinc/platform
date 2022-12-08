<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;
use ErrorException;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Field\RelatedEntityStateHelper;
use Oro\Bundle\ImportExportBundle\Validator\TypeValidationLoader;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * Common import strategy for configurable entities.
 */
class ConfigurableAddOrReplaceStrategy extends AbstractImportStrategy
{
    const STRATEGY_CONTEXT = 'configurable_add_or_replace_strategy';

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var NewEntitiesHelper */
    protected $newEntitiesHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RelatedEntityStateHelper */
    protected $relatedEntityStateHelper;

    /** @var EntityOwnershipAssociationsSetter */
    protected $entityOwnershipAssociationsSetter;

    /** @var array */
    protected $cachedEntities = [];

    /** @var object */
    protected $processingEntity;

    /** @var array */
    protected $isEntityFieldFallbackValue = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $strategyHelper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        EntityClassNameProviderInterface $entityClassNameProvider,
        TranslatorInterface $translator,
        NewEntitiesHelper $newEntitiesHelper,
        DoctrineHelper $doctrineHelper,
        RelatedEntityStateHelper $relatedEntityStateHelper
    ) {
        parent::__construct($eventDispatcher, $strategyHelper, $fieldHelper, $databaseHelper);
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->translator = $translator;
        $this->newEntitiesHelper = $newEntitiesHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->relatedEntityStateHelper = $relatedEntityStateHelper;
    }

    public function setOwnershipSetter(EntityOwnershipAssociationsSetter $entityOwnershipAssociationsSetter): void
    {
        $this->entityOwnershipAssociationsSetter = $entityOwnershipAssociationsSetter;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $this->cachedEntities = [];
        $this->processingEntity = null;
        $this->relatedEntityStateHelper->clear();

        $source = $entity;
        if (!$entity = $this->validateBeforeProcess($entity)) {
            $this->invalidateEntity($source);

            return null;
        }

        $source = $entity;
        if (!$entity = $this->beforeProcessEntity($entity)) {
            $this->invalidateEntity($source);

            return null;
        }

        $source = $entity;
        if (!$entity = $this->processEntity($entity, true, true, $this->context->getValue('itemData'))) {
            $this->invalidateEntity($source);

            return null;
        }

        $source = $entity;
        if (!$entity = $this->afterProcessEntity($entity)) {
            $this->invalidateEntity($source);

            return null;
        }

        return $this->validateAndUpdateContext($entity);
    }

    /**
     * @param object $entity
     * @param bool $isFullData
     * @param bool $isPersistNew
     * @param mixed|array|null $itemData
     * @param array $searchContext
     * @param bool $entityIsRelation
     *
     * @return null|object
     * @throws ErrorException
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = [],
        $entityIsRelation = false
    ) {
        $oid = spl_object_hash($entity);
        if (isset($this->cachedEntities[$oid])) {
            return $entity;
        }
        $entityClass = ClassUtils::getClass($entity);
        // find and cache existing or new entity
        $existingEntity = $this->findExistingEntity($entity, $searchContext);
        if ($existingEntity) {
            if (!$this->isPermissionGrantedForEntity('EDIT', $existingEntity, $entityClass)) {
                return null;
            }

            if ($cachedEntity = $this->checkCachedEntities($existingEntity)) {
                return $cachedEntity;
            }
        } else {
            if (!$this->isValidNewEntity($isPersistNew, $entityIsRelation, $entityClass, $itemData)) {
                return null;
            }

            $identityValues = $this->combineIdentityValues($entity, $entityClass, $searchContext);
            $existingEntity = $this->storeNewEntity($entity, $identityValues);
            $this->databaseHelper->resetIdentifier($entity);

            if (!$this->isPermissionGrantedForEntity('CREATE', 'entity:' . $entityClass, $entityClass)) {
                return null;
            }
            $this->cachedEntities[$oid] = $entity;
        }

        $entity = $this->importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData);

        // try to set the owner data if it absent in import data
        if (null !== $entity) {
            $this->entityOwnershipAssociationsSetter->setOwnershipAssociations($entity);
        }

        return $entity;
    }

    protected function storeNewEntity(object $entity, array $identityValues = null): ?object
    {
        return $this->newEntitiesHelper->storeNewEntity($entity, $identityValues, self::STRATEGY_CONTEXT);
    }

    /**
     * @param object $entity
     * @param null $existingEntity
     * @param array|null $itemData
     */
    protected function checkEntityAcl($entity, $existingEntity = null, $itemData = null)
    {
        $this->strategyHelper->checkImportedEntityFieldsAcl($this->context, $entity, $existingEntity, $itemData);
        $this->strategyHelper->checkEntityOwnerPermissions($this->context, $entity);
    }

    /**
     * @param object $entity
     * @param object|null $existingEntity
     * @param bool $isFullData
     * @param bool $entityIsRelation
     * @param mixed|array|null $itemData
     * @return object|null
     * @throws ErrorException
     */
    protected function importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData)
    {
        // update relations
        if ($isFullData) {
            if (!$entityIsRelation) {
                $this->processingEntity = $existingEntity ?: $entity;
            }
            $this->updateRelations($entity, $itemData);
        }

        $this->checkEntityAcl($entity, $existingEntity, $itemData);
        // import entity fields
        if ($existingEntity) {
            if ($isFullData) {
                $this->importExistingEntity($entity, $existingEntity, $itemData);
            }
            if (!$entityIsRelation) {
                $this->relatedEntityStateHelper->rememberAlteredCollectionsItems($existingEntity);
            }
            $entity = $existingEntity;
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param object $existingEntity
     * @param mixed|array|null $itemData
     * @param array $excludedFields
     */
    protected function importExistingEntity(
        $entity,
        $existingEntity,
        $itemData = null,
        array $excludedFields = []
    ) {
        $entityName = ClassUtils::getClass($entity);
        $identifierName = $this->databaseHelper->getIdentifierFieldName($entityName);
        $excludedFields[] = $identifierName;
        $fields = $this->fieldHelper->getEntityFields($entityName, EntityFieldProvider::OPTION_WITH_RELATIONS);

        foreach ($fields as $key => $field) {
            $fieldName = $field['name'];
            if ($this->isFieldExcluded($entityName, $fieldName, $itemData)) {
                $excludedFields[] = $fieldName;
                unset($fields[$key]);
            }
        }

        $this->strategyHelper->importEntity($existingEntity, $entity, $excludedFields);
    }

    /**
     * Exclude fields marked as "excluded" and skipped not identity fields
     *
     * @param string           $entityName
     * @param string           $fieldName
     * @param array|mixed|null $itemData
     *
     * @return bool
     */
    protected function isFieldExcluded($entityName, $fieldName, $itemData = null)
    {
        return $this->fieldHelper->isFieldExcluded($entityName, $fieldName, $itemData);
    }

    /**
     * @param object $entity
     * @param array|null $itemData
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @throws ErrorException
     */
    protected function updateRelations($entity, array $itemData = null)
    {
        $entityName = ClassUtils::getClass($entity);
        $fields = $this->fieldHelper->getEntityFields($entityName, EntityFieldProvider::OPTION_WITH_RELATIONS);
        $ownerFieldName = $this->databaseHelper->getOwnerFieldName($entityName);

        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $fieldName = $field['name'];
                $isFullRelation = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);
                $isPersistRelation = $this->databaseHelper->isCascadePersist($entityName, $fieldName);

                $searchContext = $this->generateSearchContextForRelationsUpdate(
                    $entity,
                    $entityName,
                    $fieldName,
                    $isPersistRelation
                );

                if ($this->fieldHelper->isSingleRelation($field)) {
                    // single relation
                    $relationEntity = $this->getObjectValue($entity, $fieldName);
                    $ownerEntity = null;

                    if ($relationEntity && $ownerFieldName === $fieldName) {
                        $identifier = $this->doctrineHelper->getSingleEntityIdentifier($relationEntity);
                        if ($identifier) {
                            $ownerEntity = $this->databaseHelper->find(
                                $this->doctrineHelper->getEntityClass($relationEntity),
                                $identifier,
                                false
                            );
                        }
                    }

                    if ($relationEntity && !$ownerEntity) {
                        $relationItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $relationEntity = $this->processEntity(
                            $relationEntity,
                            $isFullRelation,
                            $isPersistRelation,
                            $relationItemData,
                            $searchContext,
                            true
                        );
                    }
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $ownerEntity ?: $relationEntity);
                } elseif ($this->fieldHelper->isMultipleRelation($field)) {
                    // multiple relation
                    $importedCollection = $this->getObjectValue($entity, $fieldName);
                    if ($importedCollection instanceof Collection) {
                        $collectionItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        foreach ($importedCollection as $importedEntity) {
                            $entityItemData = $this->fieldHelper->getItemData(array_shift($collectionItemData));
                            $databaseEntity = $this->processEntity(
                                $importedEntity,
                                $isFullRelation,
                                $isPersistRelation,
                                $entityItemData,
                                $searchContext,
                                true
                            );

                            $key = $importedCollection->indexOf($importedEntity);
                            $importedCollection->removeElement($importedEntity);
                            if ($databaseEntity) {
                                $importedCollection->set($key, $databaseEntity);
                                $this->cacheInverseFieldRelation($entityName, $fieldName, $databaseEntity);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Temporarily save related entity inversed fields in order to recover them if import iteration fails
     *
     * @param string $entityName
     * @param string $fieldName
     * @param mixed $relationEntity
     */
    protected function cacheInverseFieldRelation($entityName, $fieldName, $relationEntity)
    {
        if (ClassUtils::getClass($relationEntity) === $this->entityName) {
            $inversedRelationFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);
            $this->relatedEntityStateHelper->rememberCollectionRelation(
                $relationEntity,
                $inversedRelationFieldName,
                $this->processingEntity
            );
        }
    }

    /**
     * @param object     $entity
     * @return object|null
     */
    protected function validateBeforeProcess($entity)
    {
        // validate entity
        $validationErrors = $this->strategyHelper->validateEntity($entity, null, [
            TypeValidationLoader::IMPORT_FIELD_TYPE_VALIDATION_GROUP
        ]);

        if ($validationErrors) {
            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors($validationErrors, $this->context);

            return null;
        }

        return $entity;
    }

    /**
     * @param object $entity
     *
     * @return null|object
     */
    protected function validateAndUpdateContext($entity)
    {
        // validate entity
        $validationErrors = $this->strategyHelper->validateEntity($entity);
        if ($validationErrors) {
            $this->processValidationErrors($entity, $validationErrors);

            return null;
        }

        $this->updateContextCounters($entity);

        return $entity;
    }

    /**
     * @param object $entity
     * @param array $validationErrors
     */
    protected function processValidationErrors($entity, array $validationErrors)
    {
        $this->context->incrementErrorEntriesCount();
        $this->strategyHelper->addValidationErrors($validationErrors, $this->context);

        $this->invalidateEntity($entity);
    }

    protected function invalidateEntity($entity)
    {
        $this->relatedEntityStateHelper->revertRelations();

        if (!$entity) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (!$em) {
            return;
        }

        try {
            $em->refresh($entity);
        } catch (ORMInvalidArgumentException $e) {
            $em->detach($entity);
        }
    }

    /**
     * Increment context counters.
     */
    protected function updateContextCounters($entity)
    {
        $identifier = $this->databaseHelper->getIdentifier($entity);
        if ($identifier || $this->newEntitiesHelper->getEntityUsage($this->getEntityHashKey($entity)) > 1) {
            $this->context->incrementReplaceCount();
        } else {
            $this->context->incrementAddCount();
        }
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    protected function getEntityHashKey($entity)
    {
        return $this->newEntitiesHelper->getEntityHashKey($entity, self::STRATEGY_CONTEXT);
    }

    /**
     * Combines identity values for entity search on local new entities storage
     * (which are not yet saved in db)
     * from search context and not empty identity fields or required identity fields
     * which could be null if configured.
     * At least one not null and not empty value must be present for search
     *
     * @param       $entity
     * @param       $entityClass
     * @param array $searchContext
     *
     * @return array|null
     */
    protected function combineIdentityValues($entity, $entityClass, array $searchContext)
    {
        if (!$this->isSearchContextValid($searchContext)) {
            return null;
        }

        $identityValues = $searchContext;
        $identityValues += $this->fieldHelper->getIdentityValues($entity);
        $notEmptyValues = [];
        $nullRequiredValues = [];
        foreach ($identityValues as $fieldName => $value) {
            if (null !== $value) {
                if ('' !== $value) {
                    $valueForIdentity = $this->generateValueForIdentityField($value);

                    if (null === $valueForIdentity) {
                        continue;
                    }

                    $notEmptyValues[$fieldName] = $valueForIdentity;
                }
            } elseif ($this->fieldHelper->isRequiredIdentityField($entityClass, $fieldName)) {
                $nullRequiredValues[$fieldName] = null;
            }
        }

        return !empty($notEmptyValues)
            ? array_merge($notEmptyValues, $nullRequiredValues)
            : null;
    }

    /**
     * @param mixed $fieldValue
     *
     * @return mixed|null
     */
    protected function generateValueForIdentityField($fieldValue)
    {
        if (false === is_object($fieldValue)) {
            return $fieldValue;
        }

        $identifier = $this->databaseHelper->getIdentifier($fieldValue);
        if ($identifier) {
            return $identifier;
        }

        $existingEntity = $this->findExistingEntity($fieldValue);
        if ($existingEntity) {
            return $this->databaseHelper->getIdentifier($existingEntity);
        }

        return null;
    }

    /**
     * Additional search parameters to find only related entities
     *
     * @param object $entity
     * @param string $entityName
     * @param string $fieldName
     * @param bool $isPersistRelation
     *
     * @return array
     */
    protected function generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation)
    {
        if (!$isPersistRelation) {
            return [];
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrineHelper->getEntityManager($entityName);
        $association = $entityManager->getClassMetadata($entityName)->getAssociationMapping($fieldName);
        // Association type should be one-to-one so context with inverse field name can lead to a single result.
        // Otherwise - if allowing one-to-many and trying to call ::findExistingEntity() with such context it will
        // always return the first item in the collection.
        if ($association['type'] !== ClassMetadata::ONE_TO_ONE) {
            return [];
        }

        $inversedFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);

        if (!$inversedFieldName) {
            return [];
        }

        return [$inversedFieldName => $entity];
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @return mixed
     * @throws ErrorException
     */
    protected function getObjectValue($entity, $fieldName)
    {
        $methodName = 'getObjectValue';

        if (is_a($entity, UserInterface::class, true)) {
            $methodName = 'getObjectValueWithReflection';
        }

        $importedEntity = $this->fieldHelper->$methodName($entity, $fieldName);
        if ($this->isEntityFieldFallbackValue(ClassUtils::getClass($entity), $fieldName)) {
            $existedEntity = $this->fieldHelper->$methodName($this->processingEntity, $fieldName);

            if ($existedEntity && $importedEntity) {
                $this->fieldHelper->setObjectValue(
                    $importedEntity,
                    'id',
                    $this->fieldHelper->getObjectValue($existedEntity, 'id')
                );
            }
        }

        return $importedEntity;
    }

    /**
     * EntityFieldFallbackValue generated automaticaly when column is empty in imported files.
     * Allow initial generation only and get data from existing entity in other cases.
     * There is no way to use identity fields for EntityFieldFallbackValue.
     */
    protected function isEntityFieldFallbackValue(string $className, string $fieldName): bool
    {
        $key = $className.'::'.$fieldName;
        if (array_key_exists($key, $this->isEntityFieldFallbackValue)) {
            return (bool)$this->isEntityFieldFallbackValue[$key];
        }

        $fields = array_filter(
            $this->fieldHelper->getEntityFields($className, EntityFieldProvider::OPTION_WITH_RELATIONS),
            function (array $field) use ($fieldName) {
                return $field['name'] === $fieldName &&
                    $this->fieldHelper->isRelation($field) &&
                    is_a($field['related_entity_name'], EntityFieldFallbackValue::class, true);
            }
        );

        $this->isEntityFieldFallbackValue[$key] = !empty($fields);

        return $this->isEntityFieldFallbackValue[$key];
    }

    /**
     * @param string $permission
     * @param object|string $entity
     * @param string $entityName
     * @return null
     */
    protected function isPermissionGrantedForEntity($permission, $entity, $entityName)
    {
        return $this->strategyHelper->checkPermissionGrantedForEntity(
            $this->context,
            $permission,
            $entity,
            $entityName
        );
    }

    /**
     * We shouldn't allow to search related entity in cache of `newEntitiesHelper`
     * if main entity is not in db and
     * main entity has one of next relations to current entity: ONE_TO_ONE or ONE_TO_MANY
     * because in another case we can face with issue of resetting inversed
     *
     * @param array $searchContext
     *
     * @return bool
     */
    protected function isSearchContextValid(array $searchContext)
    {
        foreach ($searchContext as $identityValue) {
            if (null === $identityValue || '' === $identityValue) {
                return false;
            }

            if (is_object($identityValue)) {
                $identifier = $this->databaseHelper->getIdentifier($identityValue);
                if ($identifier === null) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param object $existingEntity
     * @return object|null
     */
    protected function checkCachedEntities($existingEntity)
    {
        $existingOid = spl_object_hash($existingEntity);
        if (isset($this->cachedEntities[$existingOid])) {
            return $existingEntity;
        }
        $this->cachedEntities[$existingOid] = $existingEntity;

        return null;
    }

    /**
     * If can't find entity and new entity can't be persisted
     *
     * @param bool $isPersistNew
     * @param bool $entityIsRelation
     * @param string $entityClass
     * @param mixed|array|null $itemData
     * @return bool
     */
    protected function isValidNewEntity($isPersistNew, $entityIsRelation, string $entityClass, $itemData): bool
    {
        if (!$isPersistNew) {
            if ($entityIsRelation) {
                $class = $this->entityClassNameProvider->getEntityClassName($entityClass);
                $errorMessages = [
                    $this->translator->trans(
                        'oro.importexport.import.errors.not_found_entity',
                        [
                            '%entity_name%' => $class,
                            '%item_data%' => json_encode($itemData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        ]
                    )
                ];
                $this->strategyHelper->addValidationErrors($errorMessages, $this->context);
            }

            return false;
        }

        return true;
    }
}
