<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

class ConfigurableAddOrReplaceStrategy implements StrategyInterface, ContextAwareInterface, EntityNameAwareInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var ImportStrategyHelper
     */
    protected $strategyHelper;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var DatabaseHelper
     */
    protected $databaseHelper;

    /**
     * @var array
     */
    protected $cachedEntities = array();

    /**
     * @param ImportStrategyHelper $helper
     * @param FieldHelper $fieldHelper
     * @param DatabaseHelper $databaseHelper
     */
    public function __construct(ImportStrategyHelper $helper, FieldHelper $fieldHelper, DatabaseHelper $databaseHelper)
    {
        $this->strategyHelper = $helper;
        $this->fieldHelper = $fieldHelper;
        $this->databaseHelper = $databaseHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $this->cachedEntities = array();
        $entity = $this->beforeProcessEntity($entity);
        $entity = $this->processEntity($entity, true, true, $this->context->getValue('itemData'));
        $entity = $this->afterProcessEntity($entity);
        $entity = $this->validateAndUpdateContext($entity);

        return $entity;
    }

    /**
     * @param object $entity
     * @param bool   $isFullData
     * @param bool   $isPersistNew
     * @param mixed|array|null $itemData
     * @return null|object
     */
    protected function processEntity($entity, $isFullData = false, $isPersistNew = false, $itemData = null)
    {
        $oid = spl_object_hash($entity);
        if (isset($this->cachedEntities[$oid])) {
            return $entity;
        }

        $entityName = ClassUtils::getClass($entity);
        $fields = $this->fieldHelper->getFields($entityName, true);

        // find and cache existing or new entity
        $existingEntity = $this->findExistingEntity($entity, $fields);
        if ($existingEntity) {
            $existingOid = spl_object_hash($existingEntity);
            if (isset($this->cachedEntities[$existingOid])) {
                return $existingEntity;
            }
            $this->cachedEntities[$existingOid] = $existingEntity;
        } else {
            // if can't find entity and new entity can't be persisted
            if (!$isPersistNew) {
                return null;
            }
            $this->databaseHelper->resetIdentifier($entity);
            $this->cachedEntities[$oid] = $entity;
        }

        // import entity fields
        if ($existingEntity) {
            if ($isFullData) {
                $identifierName = $this->databaseHelper->getIdentifierFieldName($entityName);
                $excludedFields = array($identifierName);

                foreach ($fields as $key => $field) {
                    $fieldName = $field['name'];

                    // exclude fields marked as "excluded" and not specified field
                    // do not exclude identity fields
                    if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded', false)
                        || $itemData !== null && !array_key_exists($fieldName, $itemData)
                        && !$this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity', false)
                    ) {
                        $excludedFields[] = $fieldName;
                        unset($fields[$key]); // do not update relations for excluded fields
                    }
                }

                $this->strategyHelper->importEntity($existingEntity, $entity, $excludedFields);
            }

            $entity = $existingEntity;
        }

        // update relations
        if ($isFullData) {
            $this->updateRelations($entity, $fields, $itemData);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param array $fields
     * @param array|null $itemData
     */
    protected function updateRelations($entity, array $fields, array $itemData = null)
    {
        $entityName = ClassUtils::getClass($entity);

        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $fieldName = $field['name'];
                $isFullRelation = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);
                $isPersistRelation = $this->databaseHelper->isCascadePersist($entityName, $fieldName);

                // single relation
                if ($this->fieldHelper->isSingleRelation($field)) {
                    $relationEntity = $this->fieldHelper->getObjectValue($entity, $fieldName);
                    if ($relationEntity) {
                        $relationItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $relationEntity = $this->processEntity(
                            $relationEntity,
                            $isFullRelation,
                            $isPersistRelation,
                            $relationItemData
                        );
                    }
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $relationEntity);
                } elseif ($this->fieldHelper->isMultipleRelation($field)) {
                    // multiple relation
                    $relationCollection = $this->fieldHelper->getObjectValue($entity, $fieldName);
                    if ($relationCollection instanceof Collection) {
                        $collectionItemData = $this->fieldHelper->getItemData($itemData, $fieldName);
                        $collectionEntities = array();

                        foreach ($relationCollection as $collectionEntity) {
                            $entityItemData = $this->fieldHelper->getItemData(array_shift($collectionItemData));
                            $collectionEntity = $this->processEntity(
                                $collectionEntity,
                                $isFullRelation,
                                $isPersistRelation,
                                $entityItemData
                            );

                            if ($collectionEntity) {
                                $collectionEntities[] = $collectionEntity;
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
     * @param array $fields
     * @return null|object
    */
    protected function findExistingEntity($entity, array $fields)
    {
        $entityName = ClassUtils::getClass($entity);
        $identifier = $this->databaseHelper->getIdentifier($entity);
        $existingEntity = null;

        // find by identifier
        if ($identifier) {
            $existingEntity = $this->databaseHelper->find($entityName, $identifier);
        }

        // find by identity fields
        if (!$existingEntity) {
            $identityValues = array();
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                if (!$this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded', false)
                    && $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity', false)
                ) {
                    $identityValues[$fieldName] = $this->fieldHelper->getObjectValue($entity, $fieldName);
                }
            }

            // try to find entity by identity fields if at least one is specified
            foreach ($identityValues as $value) {
                if (null !== $value && '' !== $value) {
                    $existingEntity = $this->databaseHelper->findOneBy($entityName, $identityValues);
                    break;
                }
            }
        }

        return $existingEntity;
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

        // increment context counter
        $identifier = $this->databaseHelper->getIdentifier($entity);
        if ($identifier) {
            $this->context->incrementReplaceCount();
        } else {
            $this->context->incrementAddCount();
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    protected function assertEnvironment($entity)
    {
        if (!$this->context) {
            throw new LogicException('Strategy must have import/export context');
        }

        if (!$this->entityName) {
            throw new LogicException('Strategy must know about entity name');
        }

        $entityName = $this->entityName;
        if (!$entity instanceof $entityName) {
            throw new InvalidArgumentException(sprintf('Imported entity must be instance of %s', $entityName));
        }
    }

    /**
     * @param object $entity
     * @return object
     */
    protected function beforeProcessEntity($entity)
    {
        return $entity;
    }

    /**
     * @param object $entity
     * @return object
     */
    protected function afterProcessEntity($entity)
    {
        return $entity;
    }
}
