<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;

/**
 * This converter is intended to build an array contains changes made in of entity objects.
 */
class EntityToEntityChangeArrayConverter
{
    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     * @param array $changeSet
     *
     * @return array
     */
    public function convertEntityToArray(EntityManagerInterface $em, $entity, array $changeSet)
    {
        $result = [
            'entity_class' => ClassUtils::getClass($entity),
            'entity_id' => $this->getEntityId($em, $entity)
        ];
        $sanitizedChangeSet = $this->sanitizeChangeSet($em, $changeSet);
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
     *
     * @return array
     */
    private function sanitizeChangeSet(EntityManagerInterface $em, array $changeSet)
    {
        $sanitizedChangeSet = [];
        foreach ($changeSet as $property => $change) {
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
        $sanitized = $value;
        if ($value instanceof \DateTime) {
            $sanitized = $value->format(DATE_ISO8601);
        } elseif (is_object($value) && $em->contains($value)) {
            $sanitized = $this->convertEntityToArray($em, $value, []);
        } elseif (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->convertFieldValue($em, $item);
            }
        } elseif (!is_scalar($value)) {
            $sanitized = null;
        }

        return $sanitized;
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     *
     * @return int|string|null
     */
    private function getEntityId(EntityManagerInterface $em, $entity)
    {
        try {
            return $em->getUnitOfWork()->getSingleIdentifierValue($entity);
        } catch (ORMInvalidArgumentException $e) {
            throw new \LogicException(sprintf(
                'The entity "%s" has a composite identifier. The data audit does not support such identifiers.',
                ClassUtils::getClass($entity)
            ));
        }
    }
}
