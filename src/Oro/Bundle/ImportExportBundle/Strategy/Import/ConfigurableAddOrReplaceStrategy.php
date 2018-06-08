<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ConfigurableAddOrReplaceStrategy extends AbstractImportStrategy
{
    const STRATEGY_CONTEXT = 'configurable_add_or_replace_strategy';

    /** @var ChainEntityClassNameProvider */
    protected $chainEntityClassNameProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var NewEntitiesHelper */
    protected $newEntitiesHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var OwnerChecker */
    protected $ownerChecker;

    /** @var array */
    protected $cachedEntities = [];

    /** @var array */
    protected $cachedExistingEntities = [];

    /** @var array */
    protected $cachedInverseSingleRelations = [];

    /** @var array */
    protected $cachedInverseMultipleRelations = [];

    /**
     * @param EventDispatcherInterface     $eventDispatcher
     * @param ImportStrategyHelper         $strategyHelper
     * @param FieldHelper                  $fieldHelper
     * @param DatabaseHelper               $databaseHelper
     * @param ChainEntityClassNameProvider $chainEntityClassNameProvider
     * @param TranslatorInterface          $translator
     * @param NewEntitiesHelper            $newEntitiesHelper
     * @param DoctrineHelper               $doctrineHelper
     * @param OwnerChecker                 $ownerChecker
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $strategyHelper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        ChainEntityClassNameProvider $chainEntityClassNameProvider,
        TranslatorInterface $translator,
        NewEntitiesHelper $newEntitiesHelper,
        DoctrineHelper $doctrineHelper,
        OwnerChecker $ownerChecker
    ) {
        parent::__construct($eventDispatcher, $strategyHelper, $fieldHelper, $databaseHelper);
        $this->chainEntityClassNameProvider = $chainEntityClassNameProvider;
        $this->translator                   = $translator;
        $this->newEntitiesHelper            = $newEntitiesHelper;
        $this->doctrineHelper               = $doctrineHelper;
        $this->ownerChecker = $ownerChecker;
    }


    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $this->cachedEntities = [];
        $this->cachedInverseSingleRelations = [];
        $this->cachedExistingEntities = [];
        $this->cachedInverseMultipleRelations = [];

        if (!$entity = $this->beforeProcessEntity($entity)) {
            return null;
        }

        if (!$entity = $this->processEntity($entity, true, true, $this->context->getValue('itemData'))) {
            return null;
        }

        if (!$entity = $this->afterProcessEntity($entity)) {
            return null;
        }

        return $this->validateAndUpdateContext($entity);
    }

    /**
     * @param object           $entity
     * @param bool             $isFullData
     * @param bool             $isPersistNew
     * @param mixed|array|null $itemData
     * @param array            $searchContext
     * @param bool             $entityIsRelation
     *
     * @return null|object
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
            $existingOid = spl_object_hash($existingEntity);
            if (isset($this->cachedEntities[$existingOid])) {
                return $existingEntity;
            }
            $this->cachedEntities[$existingOid] = $existingEntity;
            $this->cachedExistingEntities[$existingOid] = $existingEntity;
        } else {
            // if can't find entity and new entity can't be persisted
            if (!$isPersistNew) {
                if ($entityIsRelation) {
                    $class         = $this->chainEntityClassNameProvider->getEntityClassName($entityClass);
                    $errorMessages = [$this->translator->trans(
                        'oro.importexport.import.errors.not_found_entity',
                        ['%entity_name%' => $class]
                    )];
                    $this->strategyHelper->addValidationErrors($errorMessages, $this->context);
                }

                return null;
            } else {
                /**
                 * Save new entity to newEntitiesHelper storage by key constructed from identityValues
                 * and this strategy context for reuse if there will be entity with the same identity values
                 * it has not be created again but has be fetch from this storage
                 */
                $identityValues = $this->combineIdentityValues($entity, $entityClass, $searchContext);
                if ($identityValues) {
                    $newEntityKey   = sprintf('%s:%s', $entityClass, serialize($identityValues));
                    $existingEntity = $this->newEntitiesHelper->getEntity($newEntityKey);
                    if (null === $existingEntity) {
                        $this->newEntitiesHelper->setEntity($newEntityKey, $entity);
                        $this->newEntitiesHelper->incrementEntityUsage($this->getEntityHashKey($entity));
                    } else {
                        $this->newEntitiesHelper->incrementEntityUsage($this->getEntityHashKey($existingEntity));
                    }
                }
            }

            $this->databaseHelper->resetIdentifier($entity);
            if (!$this->strategyHelper->isGranted('CREATE', 'entity:' . ClassUtils::getClass($entity))) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.access_denied_entity',
                    ['%entity_name%' => $entityClass,]
                );
                $this->context->addError($error);

                return null;
            }
            $this->cachedEntities[$oid] = $entity;
        }

        // update relations
        if ($isFullData) {
            $this->updateRelations($entity, $itemData);
        }

        // import entity fields
        if ($existingEntity) {
            $this->checkEntityAcl($entity, $existingEntity, $itemData);
            if ($isFullData) {
                $this->importExistingEntity($entity, $existingEntity, $itemData);
            }

            $entity = $existingEntity;
        } else {
            $this->checkEntityAcl($entity, null, $itemData);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param null $existingEntity
     * @param array|null $itemData
     */
    protected function checkEntityAcl($entity, $existingEntity = null, $itemData = null)
    {
        $entityName       = ClassUtils::getClass($entity);
        $identifierName   = $this->databaseHelper->getIdentifierFieldName($entityName);
        $excludedFields[] = $identifierName;
        $fields           = $this->fieldHelper->getFields($entityName, true);
        $action = $existingEntity ? 'EDIT' : 'CREATE';
        $checkEntity = $existingEntity ? $existingEntity : new ObjectIdentity('entity', $entityName);

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $importedValue = $this->getObjectValue($entity, $fieldName);
            if (!$this->strategyHelper->isGranted($action, $checkEntity, $fieldName) && $importedValue) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.access_denied_property_entity',
                    [
                        '%property_name%' => $fieldName,
                        '%entity_name%' => $entityName,
                    ]
                );
                $this->context->addError($error);
                if ($existingEntity) {
                    $existingValue = $this->fieldHelper->getObjectValue($existingEntity, $fieldName);
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $existingValue);
                } else {
                    $this->fieldHelper->setObjectValue($entity, $fieldName, null);
                }
            }
        }

        if (!$this->ownerChecker->isOwnerCanBeSet($entity)) {
            $error = $this->translator->trans(
                'oro.importexport.import.errors.wrong_owner'
            );
            $this->strategyHelper->addValidationErrors([$error], $this->context);
        }
    }

    /**
     * @param object           $entity
     * @param object           $existingEntity
     * @param mixed|array|null $itemData
     * @param array            $excludedFields
     */
    protected function importExistingEntity(
        $entity,
        $existingEntity,
        $itemData = null,
        array $excludedFields = []
    ) {
        $entityName       = ClassUtils::getClass($entity);
        $identifierName   = $this->databaseHelper->getIdentifierFieldName($entityName);
        $excludedFields[] = $identifierName;
        $fields           = $this->fieldHelper->getFields($entityName, true);

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
        $isExcluded = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded', false);
        $isIdentity = $this->isIdentityField($entityName, $fieldName, $itemData);
        $isSkipped  = $itemData !== null && !array_key_exists($fieldName, $itemData);

        return $isExcluded || $isSkipped && !$isIdentity;
    }

    /**
     * @param object     $entity
     * @param array|null $itemData
     */
    protected function updateRelations($entity, array $itemData = null)
    {
        $entityName = ClassUtils::getClass($entity);
        $fields     = $this->fieldHelper->getFields($entityName, true);

        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $fieldName         = $field['name'];
                $isFullRelation    = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);
                $isPersistRelation = $this->databaseHelper->isCascadePersist($entityName, $fieldName);
                
                $searchContext     = $this->generateSearchContextForRelationsUpdate(
                    $entity,
                    $entityName,
                    $fieldName,
                    $isPersistRelation
                );

                if ($this->fieldHelper->isSingleRelation($field)) {
                    // single relation
                    $relationEntity = $this->getObjectValue($entity, $fieldName);
                    if ($relationEntity) {
                        $relationItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $relationEntity   = $this->processEntity(
                            $relationEntity,
                            $isFullRelation,
                            $isPersistRelation,
                            $relationItemData,
                            $searchContext,
                            true
                        );

                        $this->cacheInverseFieldRelation($entityName, $fieldName, $relationEntity);
                    }
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $relationEntity);
                } elseif ($this->fieldHelper->isMultipleRelation($field)) {
                    // multiple relation
                    $relationCollection = $this->getObjectValue($entity, $fieldName);
                    if ($relationCollection instanceof Collection) {
                        $collectionItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $collectionEntities = new ArrayCollection();

                        foreach ($relationCollection as $collectionEntity) {
                            $entityItemData   = $this->fieldHelper->getItemData(array_shift($collectionItemData));
                            $collectionEntity = $this->processEntity(
                                $collectionEntity,
                                $isFullRelation,
                                $isPersistRelation,
                                $entityItemData,
                                $searchContext,
                                true
                            );

                            if ($collectionEntity) {
                                $collectionEntities->add($collectionEntity);
                                $this->cacheInverseFieldRelation($entityName, $fieldName, $collectionEntity);
                            }
                        }

                        $relationCollection->clear();
                        $this->fieldHelper->setObjectValue($entity, $fieldName, $collectionEntities);
                    }
                }
            }
        }
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

        foreach ($this->cachedExistingEntities as $oid => $object) {
            if (array_key_exists($oid, $this->cachedInverseSingleRelations)) {
                foreach ($this->cachedInverseSingleRelations[$oid] as $fieldName => $value) {
                    // restore initial value of related entity's inverse field
                    $this->fieldHelper->setObjectValue($object, $fieldName, $value);
                }
            }
        }

        foreach ($this->cachedInverseMultipleRelations as $fieldEntityPair) {
            foreach ($fieldEntityPair as $fieldName => $object) {
                /** @var PersistentCollection $collection */
                $collection = $this->fieldHelper->getObjectValue($object, $fieldName);
                if ($collection->contains($entity)) {
                    // remove entity from related entity's updated collections
                    if ($collection instanceof PersistentCollection) {
                        // fix `orphanRemoval` association
                        $tmpAssociation = $association = $collection->getMapping();
                        $tmpAssociation['orphanRemoval'] = false;
                        $collection->setOwner($collection->getOwner(), $tmpAssociation);
                        $collection->removeElement($entity);
                        $collection->setOwner($collection->getOwner(), $association);
                    } else {
                        $collection->removeElement($entity);
                    }
                }
            }
        }

        $this->doctrineHelper->getEntityManager($entity)->detach($entity);
    }

    /**
     * Increment context counters.
     *
     * @param $entity
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
        $hashKey = self::STRATEGY_CONTEXT . spl_object_hash($entity);

        return $hashKey;
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
        $notEmptyValues     = [];
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

        if (!$this->databaseHelper->isSingleInversedRelation($entityName, $fieldName)) {
            return [];
        }

        $inversedFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);

        if (!$inversedFieldName) {
            return [];
        }

        return [$inversedFieldName => $entity];
    }

    /**
     * Temporarily save related entity inversed fields in order to recover them if import iteration fails
     * @param string $entityName
     * @param string $fieldName
     * @param mixed $relationEntity
     */
    protected function cacheInverseFieldRelation($entityName, $fieldName, $relationEntity)
    {
        if (null === $relationEntity) {
            return;
        }

        $oid = spl_object_hash($relationEntity);
        $inverseFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);

        if (array_key_exists($oid, $this->cachedExistingEntities) && $inverseFieldName) {
            $relatedFields = $this->fieldHelper->getFields(ClassUtils::getClass($relationEntity), true);
            $index = array_search($inverseFieldName, array_column($relatedFields, 'name'));

            if ($index && $this->fieldHelper->isMultipleRelation($relatedFields[$index])) {
                $this->cachedInverseMultipleRelations[$oid][$inverseFieldName] = $relationEntity;
            }

            if ($index && $this->fieldHelper->isSingleRelation($relatedFields[$index])) {
                $value = $this->fieldHelper->getObjectValue($relationEntity, $inverseFieldName);
                $this->cachedInverseSingleRelations[$oid][$inverseFieldName] = $value;
            }
        }
    }

    /**
     * @param object $entity
     * @param string $fieldName
     * @return mixed
     */
    protected function getObjectValue($entity, $fieldName)
    {
        return $this->fieldHelper->getObjectValue($entity, $fieldName);
    }

    /**
     * @param string $permission
     * @param object $entity
     * @param string $entityClass
     * @return null
     */
    protected function isPermissionGrantedForEntity($permission, $entity, $entityClass)
    {
        if (!$this->strategyHelper->isGranted($permission, $entity)) {
            $error = $this->translator->trans(
                'oro.importexport.import.errors.access_denied_entity',
                ['%entity_name%' => $entityClass,]
            );
            $this->context->addError($error);

            return false;
        }

        return true;
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
     * @param string $entityName
     * @param string $fieldName
     * @param mixed  $itemData
     *
     * @return bool
     */
    private function isIdentityField($entityName, $fieldName, $itemData = null)
    {
        $isIdentity = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity', false);
        if (false === $isIdentity) {
            return $isIdentity;
        }

        $isInputDataContainsField = is_array($itemData) && array_key_exists($fieldName, $itemData);

        return $this->fieldHelper->isRequiredIdentityField($entityName, $fieldName) || $isInputDataContainsField;
    }
}
