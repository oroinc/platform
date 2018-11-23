<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type as DbalType;
use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Oro\Bundle\DataAuditBundle\Event\CollectAuditFieldsEvent;
use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\DataAuditBundle\Provider\AuditConfigProvider;
use Oro\Bundle\DataAuditBundle\Provider\EntityNameProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This converter is a part of EntityChangesToAuditEntryConverter and it is intended to process field changes.
 * @see \Oro\Bundle\DataAuditBundle\Service\EntityChangesToAuditEntryConverter
 */
class ChangeSetToAuditFieldsConverter
{
    /** @var AuditEntityMapper */
    private $auditEntityMapper;

    /** @var AuditConfigProvider */
    private $configProvider;

    /** @var EntityNameProvider */
    private $entityNameProvider;

    /** @var LoggerInterface|null */
    private $logger;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /**
     * @param AuditEntityMapper   $auditEntityMapper
     * @param AuditConfigProvider $configProvider
     * @param EntityNameProvider  $entityNameProvider
     */
    public function __construct(
        AuditEntityMapper $auditEntityMapper,
        AuditConfigProvider $configProvider,
        EntityNameProvider $entityNameProvider
    ) {
        $this->auditEntityMapper = $auditEntityMapper;
        $this->configProvider = $configProvider;
        $this->entityNameProvider = $entityNameProvider;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string        $auditEntryClass The class name of the audit entity
     * @param ClassMetadata $entityMetadata  The metadata of the audited entity
     * @param array         $changeSet       The changed data
     *
     * @return AbstractAuditField[]
     */
    public function convert($auditEntryClass, ClassMetadata $entityMetadata, array $changeSet)
    {
        $fields = [];
        foreach ($changeSet as $fieldName => $change) {
            if ($this->configProvider->isAuditableField($entityMetadata->name, $fieldName)) {
                $this->convertChangeSet($auditEntryClass, $entityMetadata, $fieldName, $change, $fields);
            }
        }

        if ($this->eventDispatcher) {
            $auditEntryFieldClass = $this->auditEntityMapper->getAuditEntryFieldClassForAuditEntry($auditEntryClass);
            $event = new CollectAuditFieldsEvent($auditEntryFieldClass, $changeSet, $fields);
            $this->eventDispatcher->dispatch(CollectAuditFieldsEvent::NAME, $event);
            $fields = $event->getFields();
        }

        return $fields;
    }

    /**
     * @param string        $auditEntryClass
     * @param ClassMetadata $entityMetadata
     * @param string        $fieldName
     * @param array         $change
     * @param array         $fields
     */
    private function convertChangeSet(
        $auditEntryClass,
        ClassMetadata $entityMetadata,
        $fieldName,
        $change,
        &$fields
    ) {
        list($old, $new) = $change;

        if ($entityMetadata->hasField($fieldName)) {
            $fieldMapping = $entityMetadata->getFieldMapping($fieldName);
            $fieldType = $fieldMapping['type'];
            if ($fieldType instanceof DbalType) {
                $fieldType = $fieldType->getName();
            }

            if ($old && in_array($fieldType, ['date', 'datetime'], true)) {
                $old = \DateTime::createFromFormat(DATE_ISO8601, $old);
            }

            if ($new && in_array($fieldType, ['date', 'datetime'], true)) {
                $new = \DateTime::createFromFormat(DATE_ISO8601, $new);
            }

            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditEntryClass,
                $fieldName,
                $fieldType,
                $new,
                $old
            );
        } elseif (isset($entityMetadata->associationMappings[$fieldName]) &&
            is_array($new) &&
            array_key_exists('inserted', $new) &&
            array_key_exists('deleted', $new)
        ) {
            $field = $this->createAuditFieldEntity($auditEntryClass, $fieldName, 'collection');
            $fields[$fieldName] = $field;
            $this->processInsertions($auditEntryClass, $new, $field);
            $this->processDeleted($auditEntryClass, $new, $field);
            $this->processChanged($auditEntryClass, $new, $field);

            $field->calculateNewValue();
        } elseif (isset($entityMetadata->associationMappings[$fieldName])) {
            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditEntryClass,
                $fieldName,
                'text',
                $this->getEntityName($auditEntryClass, $new),
                $this->getEntityName($auditEntryClass, $old)
            );
        } else {
            $fields[$fieldName] = $this->createAuditFieldEntity(
                $auditEntryClass,
                $fieldName,
                'text',
                (string)$new,
                (string)$old
            );
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processInsertions($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        foreach ($changeSet['inserted'] as $entity) {
            $entityName = $this->getEntityName($auditEntryClass, $entity);
            if ($entityName) {
                $field->addEntityAddedToCollection(
                    $entity['entity_class'],
                    $entity['entity_id'],
                    $entityName
                );
            }
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processDeleted($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if ($changeSet['deleted']) {
            foreach ($changeSet['deleted'] as $entity) {
                $entityName = $this->getEntityName($auditEntryClass, $entity);
                if ($entityName) {
                    $field->addEntityRemovedFromCollection(
                        $entity['entity_class'],
                        $entity['entity_id'],
                        $entityName
                    );
                }
            }
        }
    }

    /**
     * @param string             $auditEntryClass
     * @param array              $changeSet
     * @param AbstractAuditField $field
     */
    private function processChanged($auditEntryClass, $changeSet, AbstractAuditField $field)
    {
        if ($changeSet['changed']) {
            foreach ($changeSet['changed'] as $entity) {
                $entityName = $this->getEntityName($auditEntryClass, $entity);
                if ($entityName) {
                    $field->addEntityChangedInCollection(
                        $entity['entity_class'],
                        $entity['entity_id'],
                        $entityName
                    );
                }
            }
        }
    }

    /**
     * @param string $auditEntryClass
     * @param string $field
     * @param string $dataType
     * @param mixed  $newValue
     * @param mixed  $oldValue
     *
     * @return AbstractAuditField
     */
    private function createAuditFieldEntity(
        $auditEntryClass,
        $field,
        $dataType,
        $newValue = null,
        $oldValue = null
    ) {
        $auditEntryFieldClass = $this->auditEntityMapper->getAuditEntryFieldClassForAuditEntry($auditEntryClass);

        return new $auditEntryFieldClass($field, $dataType, $newValue, $oldValue);
    }

    /**
     * @param string     $auditEntryClass
     * @param array|null $entity
     *
     * @return string|null
     */
    private function getEntityName($auditEntryClass, $entity)
    {
        if (!$entity || !$this->validateAuditRecord($entity)) {
            return null;
        }

        return $this->entityNameProvider->getEntityName(
            $auditEntryClass,
            $entity['entity_class'],
            $entity['entity_id']
        );
    }

    /**
     * @param array $record
     *
     * @return bool
     */
    private function validateAuditRecord(array $record)
    {
        $isValid = true;
        if (!array_key_exists('entity_class', $record) || !$record['entity_class']) {
            $this->logError('The "entity_class" must not be empty.', $record);
            $isValid = false;
        } elseif (!array_key_exists('entity_id', $record) || null === $record['entity_id']) {
            $this->logError('The "entity_id" must not be null.', $record);
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * @param string $message
     * @param array  $record
     */
    private function logError($message, $record)
    {
        if (null === $this->logger) {
            return;
        }

        $this->logger->error($message, ['audit_record' => $record]);
    }
}
