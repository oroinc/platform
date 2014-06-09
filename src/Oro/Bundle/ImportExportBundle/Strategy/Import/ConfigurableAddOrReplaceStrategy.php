<?php

namespace Oro\Bundle\ImportExportBundle\Strategy\Import;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Field\FieldHelper;

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
     * @var array
     */
    protected $cachedEntities = array();

    /**
     * @param ImportStrategyHelper $helper
     * @param FieldHelper $fieldHelper
     */
    public function __construct(ImportStrategyHelper $helper, FieldHelper $fieldHelper)
    {
        $this->strategyHelper = $helper;
        $this->fieldHelper = $fieldHelper;
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
        $entity = $this->processEntity($entity, true, true);
        $entity = $this->validateAndUpdateContext($entity);

        return $entity;
    }

    /**
     * @param object $entity
     * @param bool $isFullData
     * @return null|object
     */
    protected function processEntity($entity, $isFullData = false, $isPersistNew = false)
    {
        if (isset($this->cachedEntities[spl_object_hash($entity)])) {
            return $entity;
        }

        $entityName = ClassUtils::getClass($entity);
        $fields = $this->fieldHelper->getFields($entityName, true);

        // find and cache existing or new entity
        $existingEntity = $this->findExistingEntity($entity, $fields);
        if ($existingEntity) {
            if (isset($this->cachedEntities[spl_object_hash($existingEntity)])) {
                return $existingEntity;
            }
            $this->cachedEntities[spl_object_hash($existingEntity)] = $existingEntity;
        } else {
            // if can't find entity and new entity can't be persisted
            if (!$isPersistNew) {
                return null;
            }
            $this->resetEntityIdentifier($entity);
            $this->cachedEntities[spl_object_hash($entity)] = $entity;
        }

        // import entity fields
        if ($existingEntity) {
            if ($isFullData) {
                $identifierName = $this->getEntityIdentifierFieldName($entityName);
                $excludedFields = array($identifierName);

                foreach ($fields as $field) {
                    $fieldName = $field['name'];
                    if ($this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded', false)
                        || !$isFullData
                        && !$this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity', false)
                    ) {
                        $excludedFields[] = $fieldName;
                    }
                }

                $this->strategyHelper->importEntity($existingEntity, $entity, $excludedFields);
            }
            $entity = $existingEntity;
        }

        // update relations
        if ($isFullData) {
            $this->updateRelations($entity, $fields);
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @param array $fields
     */
    protected function updateRelations($entity, array $fields)
    {
        $entityName = ClassUtils::getClass($entity);

        foreach ($fields as $field) {
            if ($this->fieldHelper->isRelation($field)) {
                $fieldName = $field['name'];
                $isFullRelation = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'full', false);
                $isPersistRelation = $this->isCascadePersist($entityName, $fieldName);

                // single relation
                if ($this->fieldHelper->isSingleRelation($field)) {
                    $relationEntity = $this->fieldHelper->getObjectValue($entity, $fieldName);
                    if ($relationEntity) {
                        $relationEntity
                            = $this->processEntity($relationEntity, $isFullRelation, $isPersistRelation);
                    }
                    $this->fieldHelper->setObjectValue($entity, $fieldName, $relationEntity);
                // multiple relation
                } elseif ($this->fieldHelper->isMultipleRelation($field)) {
                    $relationCollection = $this->fieldHelper->getObjectValue($entity, $fieldName);
                    if ($relationCollection instanceof Collection) {
                        $collectionEntities = array();
                        foreach ($relationCollection as $collectionEntity) {
                            $collectionEntity
                                = $this->processEntity($collectionEntity, $isFullRelation, $isPersistRelation);
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
     * @param $entity
     * @param array $fields
     * @return null|object
    */
    protected function findExistingEntity($entity, array $fields)
    {
        $entityName = ClassUtils::getClass($entity);
        $entityManager = $this->strategyHelper->getEntityManager($entityName);
        $identifier = $this->getEntityIdentifier($entity);
        $existingEntity = null;

        // find by identifier
        if ($identifier) {
            $existingEntity = $entityManager->find($entityName, $identifier);
        }

        // find by identity fields
        if (!$existingEntity) {
            $identityValues = array();
            foreach ($fields as $field) {
                $fieldName = $field['name'];
                if (!$this->fieldHelper->isRelation($field)
                    && !$this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded', false)
                    && $this->fieldHelper->getConfigValue($entityName, $fieldName, 'identity', false)
                ) {
                    $identityValues[$fieldName] = $this->fieldHelper->getObjectValue($entity, $fieldName);
                }
            }
            if ($identityValues) {
                $hasIdentityValues = false;
                foreach ($identityValues as $value) {
                    if (null !== $value && '' !== $value) {
                        $hasIdentityValues = true;
                        break;
                    }
                }
                if ($hasIdentityValues) {
                    $existingEntity = $entityManager->getRepository($entityName)->findOneBy($identityValues);
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
        $identifier = $this->getEntityIdentifier($entity);
        if ($identifier) {
            $this->context->incrementReplaceCount();
        } else {
            $this->context->incrementAddCount();
        }

        return $entity;
    }

    /**
     * @param object $entity
     * @return int|null
     */
    protected function getEntityIdentifier($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        $entityManager = $this->strategyHelper->getEntityManager($entityName);
        $identifier = $entityManager->getClassMetadata($entityName)->getIdentifierValues($entity);
        return current($identifier);
    }

    /**
     * @param string $entityName
     * @return string
     */
    protected function getEntityIdentifierFieldName($entityName)
    {
        $entityManager = $this->strategyHelper->getEntityManager($entityName);
        return $entityManager->getClassMetadata($entityName)->getSingleIdentifierFieldName();
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return bool
     */
    protected function isCascadePersist($entityName, $fieldName)
    {
        $metadata = $this->strategyHelper->getEntityManager($entityName)->getClassMetadata($entityName);
        $association = $metadata->getAssociationMapping($fieldName);
        return !empty($association['cascade']) && in_array('persist', $association['cascade']);
    }

    /**
     * @param object $entity
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
     */
    protected function resetEntityIdentifier($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        $identifierName = $this->getEntityIdentifierFieldName($entityName);
        $this->fieldHelper->setObjectValue($entity, $identifierName, null);
    }
}
