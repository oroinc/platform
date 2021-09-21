<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\EventListener\ORM;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;

/**
 * This class aims to reduce Database updates on unchanged Decimal field values
 */
class FixDecimalChangeSetListener
{
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $scheduledUpdates = $uow->getScheduledEntityUpdates();
        foreach ($scheduledUpdates as $entity) {
            $entityClass = ClassUtils::getClass($entity);
            $metadata = $em->getClassMetadata($entityClass);
            $this->processEntityChangeSet($uow, $metadata, $entity);
        }
    }

    private function processEntityChangeSet(UnitOfWork $uow, ClassMetadata $metadata, object $entity)
    {
        $changeSet = &$uow->getEntityChangeSet($entity);
        $isChangeSetUpdated = false;

        foreach ($changeSet as $property => $change) {
            $fieldType = $this->getFieldType($metadata, $property);

            if (in_array($fieldType, $this->getSupportedTypes())) {
                $oldValue = $this->sanitizeValue($change[0]);
                $newValue = $this->sanitizeValue($change[1]);
                if ($oldValue === $newValue) {
                    $isChangeSetUpdated = true;
                    unset($changeSet[$property]);
                }
            }
        }

        if ($isChangeSetUpdated) {
            $uow->recomputeSingleEntityChangeSet($metadata, $entity);
        }
    }

    /**
     * @return string[]
     */
    protected function getSupportedTypes(): array
    {
        return [
            Types::DECIMAL,
        ];
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function sanitizeValue($value)
    {
        return null !== $value ? (float)$value : null;
    }

    private function getFieldType(ClassMetadata $entityMetadata, string $fieldName): string
    {
        if ($entityMetadata->hasField($fieldName)) {
            $fieldType = $entityMetadata->getTypeOfField($fieldName);
            if ($fieldType instanceof Type) {
                return $fieldType->getName();
            }

            return $fieldType;
        }

        return Types::STRING;
    }
}
