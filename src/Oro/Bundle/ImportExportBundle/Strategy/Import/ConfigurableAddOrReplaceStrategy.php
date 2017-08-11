<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

/**
 * Class ConfigurableAddOrReplaceStrategy
 * @package Oro\Bundle\ImportExportBundle\Strategy\Import
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

    /** @var array */
    protected $cachedEntities = [];

    /**
     * @param EventDispatcherInterface     $eventDispatcher
     * @param ImportStrategyHelper         $strategyHelper
     * @param FieldHelper                  $fieldHelper
     * @param DatabaseHelper               $databaseHelper
     * @param ChainEntityClassNameProvider $chainEntityClassNameProvider
     * @param TranslatorInterface          $translator
     * @param NewEntitiesHelper            $newEntitiesHelper
     * @param DoctrineHelper               $doctrineHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $strategyHelper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        ChainEntityClassNameProvider $chainEntityClassNameProvider,
        TranslatorInterface $translator,
        NewEntitiesHelper $newEntitiesHelper,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($eventDispatcher, $strategyHelper, $fieldHelper, $databaseHelper);
        $this->chainEntityClassNameProvider = $chainEntityClassNameProvider;
        $this->translator                   = $translator;
        $this->newEntitiesHelper            = $newEntitiesHelper;
        $this->doctrineHelper               = $doctrineHelper;
    }


    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $this->cachedEntities = [];

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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @param object           $entity
     * @param bool             $isFullData
     * @param bool             $isPersistNew
     * @param mixed|array|null $itemData
     * @param array            $searchContext
     * @param bool             $entityIsRelation
     *
     * @return null|object
     * @todo Remove after the problem will be fixed, details in https://magecore.atlassian.net/browse/CRM-8268
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
            if (!$this->strategyHelper->isGranted("EDIT", $existingEntity)) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.access_denied_entity',
                    ['%entity_name%' => $entityClass,]
                );
                $this->context->addError($error);
                $this->temporaryContextDumper(
                    'EDIT',
                    $entityClass,
                    $isFullData,
                    $isPersistNew,
                    $itemData,
                    $searchContext,
                    $entityIsRelation
                );
                return null;
            }
            $existingOid = spl_object_hash($existingEntity);
            if (isset($this->cachedEntities[$existingOid])) {
                return $existingEntity;
            }
            $this->cachedEntities[$existingOid] = $existingEntity;
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
            if (!$this->strategyHelper->isGranted("CREATE", $entity)) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.access_denied_entity',
                    ['%entity_name%' => $entityClass,]
                );
                $this->context->addError($error);
                $this->temporaryContextDumper(
                    'CREATE',
                    $entityClass,
                    $isFullData,
                    $isPersistNew,
                    $itemData,
                    $searchContext,
                    $entityIsRelation
                );
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
     * @param array|null $itemData
     */
    protected function checkEntityAcl($entity, $existingEntity = null, $itemData = null)
    {
        $entityName       = ClassUtils::getClass($entity);
        $identifierName   = $this->databaseHelper->getIdentifierFieldName($entityName);
        $excludedFields[] = $identifierName;
        $fields           = $this->fieldHelper->getFields($entityName, true);
        $action = $existingEntity ? 'EDIT' : 'CREATE';

        foreach ($fields as $key => $field) {
            $fieldName = $field['name'];
            $importedValue = $this->fieldHelper->getObjectValue($entity, $fieldName);
            if (!$this->strategyHelper->isGranted($action, $entity, $fieldName) && $importedValue) {
                $error = $this->translator->trans(
                    'oro.importexport.import.errors.access_denied_property_entity',
                    [
                        '%property_name%' => $fieldName,
                        '%entity_name%' => $entityName,
                    ]
                );
                $this->context->addError($error);
                $this->temporaryContextDumper($action, $entityName);

                if ($existingEntity) {
                    $existingValue = $this->fieldHelper->getObjectValue($existingEntity, $fieldName);
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $existingValue);
                } else {
                    $this->fieldHelper->setObjectValue($entity, $fieldName, null);
                }
            }
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
        $isIdentity = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity', false);
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
                $inversedFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);

                // additional search parameters to find only related entities
                $searchContext = [];
                if ($isPersistRelation && $inversedFieldName
                    && $this->databaseHelper->isSingleInversedRelation($entityName, $fieldName)
                ) {
                    $searchContext[$inversedFieldName] = $entity;
                }

                if ($this->fieldHelper->isSingleRelation($field)) {
                    // single relation
                    $relationEntity = $this->fieldHelper->getObjectValue($entity, $fieldName);
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
                    }
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $relationEntity);
                } elseif ($this->fieldHelper->isMultipleRelation($field)) {
                    // multiple relation
                    $relationCollection = $this->fieldHelper->getObjectValue($entity, $fieldName);
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
                                $searchContext
                            );

                            if ($collectionEntity) {
                                $collectionEntities->add($collectionEntity);
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
            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors($validationErrors, $this->context);

            $this->doctrineHelper->getEntityManager($entity)->detach($entity);

            return null;
        }

        $this->updateContextCounters($entity);

        return $entity;
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
                    if (is_object($value)) {
                        $identifier = $this->databaseHelper->getIdentifier($value);
                        if ($identifier !== null) {
                            $notEmptyValues[$fieldName] = $identifier;
                        }
                    } else {
                        $notEmptyValues[$fieldName] = $value;
                    }
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
     * @param string           $action
     * @param string           $entityName
     * @param bool             $isFullData
     * @param bool             $isPersistNew
     * @param mixed|array|null $itemData
     * @param array            $searchContext
     * @param bool             $entityIsRelation
     *
     * @deprecated Method should not been used. Created only in purpose of Access Denied issues investigation.
     * @todo Remove after the problem will be fixed, details in https://magecore.atlassian.net/browse/CRM-8268
     */
    private function temporaryContextDumper(
        $action,
        $entityName,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = [],
        $entityIsRelation = false
    ) {
        if ($this->container !== null) {
            $errorContext = [
                'action' => $action,
                'entityName' => $entityName,
                'isFullData' => $isFullData,
                'isPersistNew' => $isPersistNew,
                'itemData' => $itemData,
                'searchContext' => $searchContext,
                'entityIsRelation' => $entityIsRelation,
                'securityFacade' => $this->container->get('oro_security.security_facade'),
                'trace' => debug_backtrace()
            ];

            $this->container->get('logger')->debug(
                'Access Denied in ConfigurableAddOrReplaceStrategy',
                $errorContext
            );
        }
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
}
