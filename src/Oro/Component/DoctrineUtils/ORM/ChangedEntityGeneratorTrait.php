<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\UnitOfWork;

/**
 * Handy methods for working with changed entities in UnitOfWork.
 */
trait ChangedEntityGeneratorTrait
{
    /**
     * @param UnitOfWork $uow
     *
     * @return \Generator
     */
    protected function getChangedEntities(UnitOfWork $uow)
    {
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            yield $entity;
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            yield $entity;
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            yield $entity;
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return \Generator
     */
    protected function getCreatedAndDeletedEntities(UnitOfWork $uow)
    {
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            yield $entity;
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            yield $entity;
        }
    }

    protected function getInsertedAndUpdatedEntities(UnitOfWork $uow): \Generator
    {
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            yield $entity;
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            yield $entity;
        }
    }
}
