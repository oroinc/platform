<?php

namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;
use Oro\Bundle\DataAuditBundle\Provider\AuditFieldTypeProvider;

/**
 * This converter is intended to build an array contains changes made in of entity objects.
 */
class EntityToEntityChangeArrayConverter
{
    /** @var AuditFieldTypeProvider */
    private $auditFieldTypeProvider;

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     * @param array $changeSet
     *
     * @return array
     */
    public function convertEntityToArray(EntityManagerInterface $em, $entity, array $changeSet)
    {
        return $this->convertNamedEntityToArray($em, $entity, $changeSet);
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     * @param array $changeSet
     * @param string $entityName
     * @return array
     */
    public function convertNamedEntityToArray(EntityManagerInterface $em, $entity, array $changeSet, $entityName = null)
    {
        $entityClass = ClassUtils::getClass($entity);
        $result = [
            'entity_class' => $entityClass,
            'entity_id' => $this->getEntityId($em, $entity, $changeSet),
        ];

        if ($entityName) {
            $result['entity_name'] = $entityName;
        }

        $sanitizedChangeSet = $this->sanitizeChangeSet($em, $changeSet, $entityClass);
        if (!empty($sanitizedChangeSet)) {
            $result['change_set'] = $sanitizedChangeSet;
        }

        if ($entity instanceof AuditAdditionalFieldsInterface) {
            $additionalFields = $entity->getAdditionalFields();
            if (!empty($additionalFields)) {
                $additionalFields = $this->sanitizeAdditionalFields($em, $additionalFields);
                if (!empty($additionalFields)) {
                    $result['additional_fields'] = $additionalFields;
                }
            }
        }

        return $result;
    }

    /**
     * @param EntityManagerInterface $em
     * @param array $changeSet
     * @param string $entityClass
     * @return array
     */
    private function sanitizeChangeSet(EntityManagerInterface $em, array $changeSet, string $entityClass)
    {
        $metadata = $em->getClassMetadata($entityClass);

        $sanitizedChangeSet = [];
        foreach ($changeSet as $property => $change) {
            $fieldType = $this->auditFieldTypeProvider->getFieldType($metadata, $property);
            if (!AuditFieldTypeRegistry::hasType($fieldType)) {
                continue;
            }

            $sanitizedOld = $this->convertFieldValue($em, $change[0]);
            $sanitizedNew = $this->convertFieldValue($em, $change[1]);

            if ($sanitizedOld === $sanitizedNew) {
                continue;
            }

            $sanitizedChangeSet[$property] = [$sanitizedOld, $sanitizedNew];
        }

        return $sanitizedChangeSet;
    }

    /**
     * @param EntityManagerInterface $em
     * @param array $fields
     *
     * @return array
     */
    private function sanitizeAdditionalFields(EntityManagerInterface $em, array $fields)
    {
        $sanitizedFields = [];
        foreach ($fields as $property => $change) {
            $sanitizedFields[$property] = $this->convertFieldValue($em, $change);
        }

        return $sanitizedFields;
    }

    /**
     * @param EntityManagerInterface $em
     * @param mixed $value
     * @return mixed
     */
    private function convertFieldValue(EntityManagerInterface $em, $value)
    {
        if ($value instanceof \DateTime) {
            return $value->format(\DateTime::ISO8601);
        }

        if (is_object($value)) {
            if ($em->getMetadataFactory()->hasMetadataFor(ClassUtils::getClass($value))) {
                return $this->convertNamedEntityToArray($em, $value, []);
            }

            return null;
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->convertFieldValue($em, $item);
            }

            return $sanitized;
        }

        if (!is_scalar($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     * @param array $changeSet
     * @return int|string|null
     */
    private function getEntityId(EntityManagerInterface $em, $entity, array $changeSet)
    {
        try {
            $id = $em->getUnitOfWork()->getSingleIdentifierValue($entity);
            if ($id !== null) {
                return $id;
            }

            $classMetadata = $em->getClassMetadata(ClassUtils::getClass($entity));
            $identifierField = $classMetadata->getSingleIdentifierFieldName();

            if (array_key_exists($identifierField, $changeSet)) {
                $oldVal = reset($changeSet[$identifierField]);
                $newVal = end($changeSet[$identifierField]);

                if ($newVal !== null) {
                    return $newVal;
                }

                if ($oldVal !== null) {
                    return $oldVal;
                }
            }

            return null;
        } catch (ORMInvalidArgumentException $e) {
            throw new \LogicException(
                sprintf(
                    'The entity "%s" has a composite identifier. The data audit does not support such identifiers.',
                    ClassUtils::getClass($entity)
                )
            );
        }
    }

    public function setAuditFieldTypeProvider(AuditFieldTypeProvider $auditFieldTypeProvider)
    {
        $this->auditFieldTypeProvider = $auditFieldTypeProvider;
    }
}
