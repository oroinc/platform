<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;

class ConfigurableAddOrReplaceStrategy extends AbstractImportStrategy implements
    ContextAwareInterface,
    EntityNameAwareInterface
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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

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
     * @param EventDispatcherInterface $eventDispatcher
     * @param ImportStrategyHelper $helper
     * @param FieldHelper $fieldHelper
     * @param DatabaseHelper $databaseHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImportStrategyHelper $helper,
        FieldHelper $fieldHelper,
        DatabaseHelper $databaseHelper
    ) {
        parent::__construct($eventDispatcher);

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
     * @param array $searchContext
     * @return null|object
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = array()
    ) {
        $oid = spl_object_hash($entity);
        if (isset($this->cachedEntities[$oid])) {
            return $entity;
        }

        $entityName = ClassUtils::getClass($entity);
        $fields = $this->fieldHelper->getFields($entityName, true);

        // find and cache existing or new entity
        $existingEntity = $this->findExistingEntity($entity, $fields, $searchContext);
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
                $this->importExistingEntity($entity, $existingEntity, $itemData);
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
                            $searchContext
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
     * @param array $fields
     * @param array $searchContext
     * @return null|object
     */
    protected function findExistingEntity($entity, array $fields, array $searchContext = array())
    {
        $entityName = ClassUtils::getClass($entity);
        $identifier = $this->databaseHelper->getIdentifier($entity);
        $existingEntity = null;

        // find by identifier
        if ($identifier) {
            $existingEntity = $this->databaseHelper->find($entityName, $identifier);
        }

        // find by identity fields
        if (!$existingEntity
            && (!$searchContext || $this->databaseHelper->getIdentifier(current($searchContext)))
        ) {
            $identityValues = $searchContext;
            $identityValues += $this->fieldHelper->getIdentityValues($entity, $fields);
            $existingEntity = $this->findEntityByIdentityValues($entityName, $identityValues);
        }

        return $existingEntity;
    }

    /**
     * Try to find entity by identity fields if at least one is specified
     *
     * @param string $entityName
     * @param array $identityValues
     * @return null|object
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        foreach ($identityValues as $value) {
            if (null !== $value && '' !== $value) {
                return $this->databaseHelper->findOneBy($entityName, $identityValues);
            }
        }

        return null;
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
}
