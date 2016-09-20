<?php
namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

class ConvertEntityToArrayForMessageQueueService
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

        return [
            'entity_class' => $entityClass,
            'entity_id' => $this->getEntityIdentifier($em, $entity),
            'change_set' => $this->sanitizeChangeSet($em, $changeSet),
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
            $sanitizedNew = $new = $change[1];
            $sanitizedOld = $old = $change[0];

            if ($old instanceof \DateTime) {
                $sanitizedOld = $old->format(DATE_ISO8601);
            } elseif (is_object($old) && $em->contains($old)) {
                $sanitizedOld = $this->convertEntityToArray($em, $old, []);
            } elseif (is_object($old)) {
                continue;
            }

            if ($new instanceof \DateTime) {
                $sanitizedNew = $new->format(DATE_ISO8601);
            } elseif (is_object($new) && $em->contains($new)) {
                $sanitizedNew = $this->convertEntityToArray($em, $new, []);
            } elseif (is_object($new)) {
                continue;
            }

            if ($sanitizedOld === $sanitizedNew) {
                continue;
            }

            $sanitizedChangeSet[$property] = [$sanitizedOld, $sanitizedNew];
        }

        return $sanitizedChangeSet;
    }

    /**
     * @param EntityManagerInterface $em
     * @param object $entity
     *
     * @return array
     */
    private function getEntityIdentifier(EntityManagerInterface $em, $entity)
    {
        $identifier = [];

        $entityMeta = $em->getClassMetadata(get_class($entity));
        foreach ($entityMeta->getIdentifierValues($entity) as $field => $value) {
            if ($entityMeta->hasAssociation($field)) {
                $associationMeta = $em->getClassMetadata(get_class($value));
                $idFieldName = $associationMeta->getSingleIdentifierFieldName();
                $identifier[$field] = $associationMeta->getFieldValue($value, $idFieldName);
            } else {
                $identifier[$field] = $value;
            }
        }

        return $identifier;
    }
}
