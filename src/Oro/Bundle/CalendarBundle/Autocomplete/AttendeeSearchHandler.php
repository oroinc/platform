<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class AttendeeSearchHandler extends ContextSearchHandler
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * {@inheritdoc}
     *
     * @param AttendeeRelationManager $attendeeRelationManager
     * @param string|null             $class
     */
    public function __construct(
        TokenStorageInterface $token,
        TranslatorInterface $translator,
        Indexer $indexer,
        ActivityManager $activityManager,
        ConfigManager $configManager,
        EntityClassNameHelper $entityClassNameHelper,
        ObjectManager $objectManager,
        ObjectMapper $mapper,
        EventDispatcherInterface $dispatcher,
        AttendeeRelationManager $attendeeRelationManager,
        $class = null
    ) {
        parent::__construct(
            $token,
            $translator,
            $indexer,
            $activityManager,
            $configManager,
            $entityClassNameHelper,
            $objectManager,
            $mapper,
            $dispatcher,
            $class
        );
        $this->attendeeRelationManager = $attendeeRelationManager;
    }

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
}
