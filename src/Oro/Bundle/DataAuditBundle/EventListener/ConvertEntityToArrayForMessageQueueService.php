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
            'entity_id' => $this->getEntityId($em, $entity),
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
     * @return int|string
     */
    private function getEntityId(EntityManagerInterface $em, $entity)
    {
        $entityMeta = $em->getClassMetadata(get_class($entity));
        $idFieldName = $entityMeta->getSingleIdentifierFieldName();

        return $entityMeta->getReflectionProperty($idFieldName)->getValue($entity);
    }
}
