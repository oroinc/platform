<?php

namespace Oro\Bundle\CalendarBundle\Datagrid\MassAction;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Query;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\DataGridBundle\Datasource\Orm\DeletionIterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler as ParentHandler;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

/**
 * Class DeleteMassActionHandler
 *
 * @package Oro\Bundle\CalendarBundle\Datagrid\MassAction
 */
class DeleteMassActionHandler extends ParentHandler
{
    /**
     * {@inheritDoc}
     */
    protected function doDelete(MassActionHandlerArgs $args)
    {
        $iteration    = 0;
        $entityName   = $this->getEntityName($args);
        $queryBuilder = $args->getResults()->getSource();
        $results      = new DeletionIterableResult($queryBuilder);
        $results->setBufferSize(self::FLUSH_BATCH_SIZE);
        $this->listenerManager->disableListeners(['oro_search.index_listener']);
        // if huge amount data must be deleted
        set_time_limit(0);
        $deletedIds            = [];
        $entityIdentifiedField = $this->getEntityIdentifierField($args);
        /** @var EntityManager $manager */
        $manager = $this->registry->getManagerForClass($entityName);
        foreach ($results as $result) {
            /** @var $result ResultRecordInterface */
            $entity          = $result->getRootEntity();
            $identifierValue = $result->getValue($entityIdentifiedField);
            if (!$entity) {
                // no entity in result record, it should be extracted from DB
                $entity = $manager->getReference($entityName, $identifierValue);
            }

            if ($entity) {
                $deletedIds[] = $identifierValue;
                $this->processCalendarEventDelete($entity, $manager);
                $iteration++;

                if ($iteration % self::FLUSH_BATCH_SIZE == 0) {
                    $this->finishBatch($manager, $entityName, $deletedIds);
                    $deletedIds = [];
                }
            }
        }

        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $this->finishBatch($manager, $entityName, $deletedIds);
        }

        return $this->getDeleteResponse($args, $iteration);
    }

    /**
     * @param CalendarEvent $entity
     * @param EntityManager $manager
     *
     * @return DeleteMassActionHandler
     */
    protected function processCalendarEventDelete(CalendarEvent $entity, EntityManager $manager)
    {
        if ($entity->getRecurringEvent()) {
            $event = $entity->getRealCalendarEvent();
            $event->setCancelled(true)
                ->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

            $childEvents = $event->getChildEvents();
            foreach ($childEvents as $childEvent) {
                $childEvent->setCancelled(true)
                    ->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
            }
        } else {
            if ($entity->getRecurrence() && $entity->getRecurrence()->getId()) {
                $manager->remove($entity->getRecurrence());
            }

            if ($entity->getRecurringEvent()) {
                $event = $entity->getRealCalendarEvent();
                $childEvents = $event->getChildEvents();
                foreach ($childEvents as $childEvent) {
                    $manager->remove($childEvent);
                }
            }
            $manager->remove($entity);
        }

        return $this;
    }
}
