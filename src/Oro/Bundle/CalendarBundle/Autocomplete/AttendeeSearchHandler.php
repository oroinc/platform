<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class AttendeeSearchHandler extends ContextSearchHandler
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $groupped = $this->groupIdsByEntityName($items);

        $objects = [];
        foreach ($groupped as $entityName => $ids) {
            $objects = array_merge(
                $objects,
                $this->objectManager
                    ->getRepository($entityName)
                    ->findById($ids)
            );
        }

        $result = [];
        foreach ($objects as $object) {
            $attendee = $this->attendeeRelationManager->createAttendee($object);
            if (!$attendee) {
                throw new \LogicException(
                    'Attendee couldn\'t be created for "%s" entity',
                    ClassUtils::getClass($object)
                );
            }

            $result[] = [
                'id'          => json_encode(
                    [
                        'entityClass' => ClassUtils::getClass($object),
                        'entityId'    => $object->getId(),
                    ]
                ),
                'text'        => $attendee->getDisplayName(),
                'displayName' => $attendee->getDisplayName(),
                'email'       => $attendee->getEmail(),
                'status'      => $attendee->getStatus() ? $attendee->getStatus()->getId() : null,
                'type'        => $attendee->getType() ? $attendee->getType()->getId() : null,
            ];
        }

        return $result;
    }

    /**
     * @param Item[] $items
     *
     * @return array
     */
    protected function groupIdsByEntityName(array $items)
    {
        $groupped = [];
        foreach ($items as $item) {
            $groupped[$item->getEntityName()][] = $item->getRecordId();
        }

        return $groupped;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchAliases()
    {
        return ['oro_user'];
    }

    /**
     * @param AttendeeRelationManager $attendeeRelationManager
     *
     * @return AttendeeSearchHandler
     */
    public function setAttendeeRelationManager(AttendeeRelationManager $attendeeRelationManager)
    {
        $this->attendeeRelationManager = $attendeeRelationManager;

        return $this;
    }
}
