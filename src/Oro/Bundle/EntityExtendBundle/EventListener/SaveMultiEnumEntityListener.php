<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Inflector\Inflector;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Ensures multi-enums are properly flushed.
 */
class SaveMultiEnumEntityListener
{
    private Inflector $inflector;

    public function __construct(Inflector $inflector)
    {
        $this->inflector = $inflector;
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $this->processCollections($uow->getScheduledCollectionUpdates(), $em, $uow);
        $this->processCollections($uow->getScheduledCollectionDeletions(), $em, $uow);
    }

    private function processCollections(array $collections, EntityManagerInterface $em, UnitOfWork $uow): void
    {
        // check if multi-enum modifications exist
        $updates = [];
        /** @var PersistentCollection $coll */
        foreach ($collections as $coll) {
            $mapping = $coll->getMapping();
            if ($mapping['type'] === ClassMetadata::MANY_TO_MANY
                && $mapping['isOwningSide']
                && is_subclass_of($mapping['targetEntity'], ExtendHelper::BASE_ENUM_VALUE_CLASS)
            ) {
                $owner = $coll->getOwner();
                $suffix = $this->getSnapshotFieldMethodSuffix($mapping['fieldName']);
                $snapshotValue = $this->buildSnapshotValue($coll);
                if ($owner->{'get' . $suffix}() !== $snapshotValue) {
                    $ownerMetadata = $em->getClassMetadata(ClassUtils::getClass($owner));
                    $updates[] = [$ownerMetadata, $owner, 'set' . $suffix, $snapshotValue];
                }
            }
        }
        // if any, update corresponding snapshots
        // $item = [entityClassMetadata, entity, snapshotFieldSetter, snapshotValue]
        foreach ($updates as $item) {
            $item[1]->{$item[2]}($item[3]);
            $uow->recomputeSingleEntityChangeSet($item[0], $item[1]);
        }
    }

    private function buildSnapshotValue(PersistentCollection $coll): ?string
    {
        if ($coll->isEmpty()) {
            return null;
        }

        $ids = [];
        /** @var AbstractEnumValue $item */
        foreach ($coll as $item) {
            $ids[] = $item->getId();
        }
        \sort($ids);
        $snapshot = \implode(',', $ids);

        if (\strlen($snapshot) > ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH) {
            $snapshot = \substr($snapshot, 0, ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH);
            $lastDelim = strrpos($snapshot, ',');
            if (ExtendHelper::MAX_ENUM_SNAPSHOT_LENGTH - $lastDelim - 1 < 3) {
                $lastDelim = strrpos($snapshot, ',', -(\strlen($snapshot) - $lastDelim + 1));
            }
            $snapshot = substr($snapshot, 0, $lastDelim + 1) . '...';
        }

        return $snapshot;
    }

    private function getSnapshotFieldMethodSuffix(string $fieldName): string
    {
        return $this->inflector->classify(ExtendHelper::getMultiEnumSnapshotFieldName($fieldName));
    }
}
