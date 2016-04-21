<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

class ConfigurableAddOrReplaceStrategy extends AbstractImportStrategy
{
    /** @var ChainEntityClassNameProvider */
    protected $chainEntityClassNameProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var array */
    protected $cachedEntities = array();

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ImportStrategyHelper $strategyHelper
     * @param FieldHelper $fieldHelper
     * @param DatabaseHelper $databaseHelper
     * @param ChainEntityClassNameProvider $chainEntityClassNameProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $strategyHelper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper,
        ChainEntityClassNameProvider $chainEntityClassNameProvider,
        TranslatorInterface $translator
    ) {
        parent::__construct($eventDispatcher, $strategyHelper, $fieldHelper, $databaseHelper);
        $this->chainEntityClassNameProvider = $chainEntityClassNameProvider;
        $this->translator = $translator;
    }


    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $this->cachedEntities = array();

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
     * @param object $entity
     * @param bool   $isFullData
     * @param bool   $isPersistNew
     * @param mixed|array|null $itemData
     * @param array $searchContext
     * @param bool $entityIsRelation
     *
     * @return null|object
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = array(),
        $entityIsRelation = false
    ) {
        $oid = spl_object_hash($entity);
        if (isset($this->cachedEntities[$oid])) {
            return $entity;
        }

        // find and cache existing or new entity
        $existingEntity = $this->findExistingEntity($entity, $searchContext);
        if ($existingEntity) {
            $existingOid = spl_object_hash($existingEntity);
            if (isset($this->cachedEntities[$existingOid])) {
                return $existingEntity;
            }
            $this->cachedEntities[$existingOid] = $existingEntity;
        } else {
            // if can't find entity and new entity can't be persisted
            if (!$isPersistNew) {
                if ($entityIsRelation) {
                    $class = $this->chainEntityClassNameProvider->getEntityClassName(ClassUtils::getClass($entity));
                    $errorMessages = [$this->translator->trans(
                        'oro.importexport.import.errors.not_found_entity',
                        ['%entity_name%' => $class]
                    )];
                    $this->strategyHelper->addValidationErrors($errorMessages, $this->context);
                }

                return null;
            }

            $this->databaseHelper->resetIdentifier($entity);
            $this->cachedEntities[$oid] = $entity;
        }

        // update relations
        if ($isFullData) {
            $this->updateRelations($entity, $itemData);
        }

        // import entity fields
        if ($existingEntity) {
            if ($isFullData) {
                $this->importExistingEntity($entity, $existingEntity, $itemData);
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
        array $excludedFields = array()
    ) {
        $entityName = ClassUtils::getClass($entity);
        $identifierName = $this->databaseHelper->getIdentifierFieldName($entityName);
        $excludedFields[] = $identifierName;
        $fields = $this->fieldHelper->getFields($entityName, true);

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
     * @param string $entityName
     * @param string $fieldName
     * @param array|mixed|null $itemData
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
     * @param object $entity
     * @param array|null $itemData
     */
    protected function updateRelations($entity, array $itemData = null)
    {
        $entityName = ClassUtils::getClass($entity);
        $fields = $this->fieldHelper->getFields($entityName, true);

        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $fieldName = $field['name'];
                $isFullRelation = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);
                $isPersistRelation = $this->databaseHelper->isCascadePersist($entityName, $fieldName);
                $inversedFieldName = $this->databaseHelper->getInversedRelationFieldName($entityName, $fieldName);

                // additional search parameters to find only related entities
                $searchContext = array();
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
                        $relationEntity = $this->processEntity(
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
                            $entityItemData = $this->fieldHelper->getItemData(array_shift($collectionItemData));
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
     * @return null|object
     */
    protected function validateAndUpdateContext($entity)
    {
        // validate entity
        $validationErrors = $this->strategyHelper->validateEntity($entity);
        if ($validationErrors) {
            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors($validationErrors, $this->context);

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
        if ($identifier) {
            $this->context->incrementReplaceCount();
        } else {
            $this->context->incrementAddCount();
        }
    }
}
