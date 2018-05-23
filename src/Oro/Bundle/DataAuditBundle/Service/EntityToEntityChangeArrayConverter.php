<?php
namespace Oro\Bundle\DataAuditBundle\Service;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;

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
        $entityClass = ClassUtils::getClass($entity);

        $additionalFields = [];

        if ($entity instanceof AuditAdditionalFieldsInterface && $entity->getAdditionalFields()) {
            $additionalFields = $this->sanitizeAdditionalFields($em, $entity->getAdditionalFields());
        }

        return [
            'entity_class' => $entityClass,
            'entity_id' => $this->getEntityId($em, $entity),
            'change_set' => $this->sanitizeChangeSet($em, $changeSet),
            'additional_fields' => $additionalFields
        ];
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
     * @return int|string
     */
    private function getEntityId(EntityManagerInterface $em, $entity)
    {
        return $em->getClassMetadata(ClassUtils::getClass($entity))
            ->getSingleIdReflectionProperty()
            ->getValue($entity);
    }
}
