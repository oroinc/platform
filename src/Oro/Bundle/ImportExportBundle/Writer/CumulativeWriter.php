<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Psr\Log\LoggerAwareTrait;

/**
 * Write entities to DB with dynamic batch size based on UoW amount of objects
 */
class CumulativeWriter implements ItemWriterInterface, ClosableInterface
{
    use LoggerAwareTrait;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var int */
    private $maxChangedEntities = 1000;

    /** @var int */
    private $maxHydratedAndChangedEntities = 1000;

    /** @var int */
    private $maxHydratedEntities = 2000;

    /** @var string[] */
    private $ignoredChanges = [];

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function write(array $items)
    {
        if ($this->skipFlush($items)) {
            return;
        }

        $this->doWrite();
    }

    private function doWrite()
    {
        $entityManager = $this->doctrineHelper->getManager();

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();

        $changes = array_filter(
            [
                'ScheduledEntityInsertions' => $this->getChangesCountByClasses(
                    $unitOfWork->getScheduledEntityInsertions()
                ),
                'ScheduledEntityUpdates' => $this->getChangesCountByClasses(
                    $unitOfWork->getScheduledEntityUpdates()
                ),
                'ScheduledEntityDeletions' => $this->getChangesCountByClasses(
                    $unitOfWork->getScheduledEntityDeletions()
                ),
                'ScheduledCollectionUpdates' => $this->getCollectionChangesCountByClasses(
                    $unitOfWork->getScheduledCollectionUpdates()
                ),
                'ScheduledCollectionDeletions' => $this->getCollectionChangesCountByClasses(
                    $unitOfWork->getScheduledCollectionDeletions()
                ),
            ]
        );
        if ($changes) {
            $this->logger->debug('Writing batch of changes to the database', $changes);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    private function skipFlush(array &$items): bool
    {
        $entityManager = $this->doctrineHelper->getManager();
        foreach ($items as $item) {
            if (!is_object($item) || !$this->doctrineHelper->isManageableEntity($item)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Value must be an object, "%s" of type "%s" given',
                        serialize($item),
                        gettype($item)
                    )
                );
            }

            $entityManager->persist($item);
        }

        /** @var UnitOfWork $unitOfWork */
        $unitOfWork = $entityManager->getUnitOfWork();
        if ($this->shouldFlush($unitOfWork)) {
            return false;
        }

        foreach ($items as $item) {
            $metadata = $this->doctrineHelper->getEntityMetadataForClass($this->doctrineHelper->getEntityClass($item));

            if ($unitOfWork->getOriginalEntityData($item)) {
                /** UnitOfWork#computeChangeSet($item) called manually in a listener */
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $item);
            } else {
                $unitOfWork->computeChangeSet($metadata, $item);
            }
        }

        if ($this->shouldFlush($unitOfWork)) {
            return false;
        }

        return true;
    }

    private function shouldFlush(UnitOfWork $unitOfWork): bool
    {
        $changedEntities = $this->getUnitOfWorkChangesCount($unitOfWork);
        if ($changedEntities >= $this->maxChangedEntities) {
            return true;
        }

        $hydratedEntities = 0;
        foreach ($unitOfWork->getIdentityMap() as $class => $entities) {
            $hydratedEntities += count($entities);
        }

        if ($changedEntities && $hydratedEntities >= $this->maxHydratedAndChangedEntities) {
            return true;
        }

        if ($hydratedEntities >= $this->maxHydratedEntities) {
            return true;
        }

        return false;
    }

    private function getChangesCountByClasses(array $changes): array
    {
        $changesByClass = [];

        foreach ($changes as $change) {
            $entityClass = $this->doctrineHelper->getEntityClass($change);
            if (empty($changesByClass[$entityClass])) {
                $changesByClass[$entityClass] = 0;
            }

            $changesByClass[$entityClass]++;
        }

        return $changesByClass;
    }

    private function getCollectionChangesCountByClasses(array $changes): array
    {
        $changesByClass = [];

        /** @var PersistentCollection $change */
        foreach ($changes as $change) {
            $entityClass = $this->doctrineHelper->getEntityClass($change->getOwner());
            $key = sprintf('%s#%s', $entityClass, $change->getMapping()['fieldName']);
            if (empty($changesByClass[$key])) {
                $changesByClass[$key] = 0;
            }

            $changesByClass[$key]++;
        }

        return $changesByClass;
    }

    private function getUnitOfWorkChangesCount(UnitOfWork $unitOfWork): int
    {
        $scheduledEntityInsertions = $this->getChangesCountByClasses($unitOfWork->getScheduledEntityInsertions());
        $scheduledEntityUpdates = $this->getChangesCountByClasses($unitOfWork->getScheduledEntityUpdates());
        $scheduledEntityDeletions = $this->getChangesCountByClasses($unitOfWork->getScheduledEntityDeletions());
        $scheduledCollectionUpdates = $this->getCollectionChangesCountByClasses(
            $unitOfWork->getScheduledCollectionUpdates()
        );
        $scheduledCollectionDeletions = $this->getCollectionChangesCountByClasses(
            $unitOfWork->getScheduledCollectionDeletions()
        );

        foreach ($this->ignoredChanges as $ignoredChange) {
            unset($scheduledEntityInsertions[$ignoredChange]);
            unset($scheduledEntityUpdates[$ignoredChange]);
            unset($scheduledEntityDeletions[$ignoredChange]);
            unset($scheduledCollectionUpdates[$ignoredChange]);
            unset($scheduledCollectionDeletions[$ignoredChange]);
        }

        return
            array_sum($scheduledEntityInsertions) +
            array_sum($scheduledEntityUpdates) +
            array_sum($scheduledEntityDeletions) +
            array_sum($scheduledCollectionUpdates) +
            array_sum($scheduledCollectionDeletions);
    }

    public function close()
    {
        $this->doWrite();
    }

    public function setMaxChangedEntities(int $maxChangedEntities): void
    {
        $this->maxChangedEntities = $maxChangedEntities;
    }

    public function setMaxHydratedAndChangedEntities(int $maxHydratedAndChangedEntities): void
    {
        $this->maxHydratedAndChangedEntities = $maxHydratedAndChangedEntities;
    }

    public function setMaxHydratedEntities(int $maxHydratedEntities): void
    {
        $this->maxHydratedEntities = $maxHydratedEntities;
    }

    public function addIgnoredChange(string $ignoredChange)
    {
        $this->ignoredChanges[$ignoredChange] = $ignoredChange;
    }
}
