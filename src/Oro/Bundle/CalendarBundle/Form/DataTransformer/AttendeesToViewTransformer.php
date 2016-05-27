<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActivityBundle\Form\DataTransformer\ContextsToViewTransformer;
use Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AttendeesToViewTransformer extends ContextsToViewTransformer
{
    /** @var AttendeeRelationManager */
    protected $attendeeRelationManager;

    /**
     * @param EntityManager $entityManager
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param ObjectMapper $mapper
     * @param TokenStorageInterface $securityTokenStorage
     * @param EventDispatcherInterface $dispatcher
     * @param AttendeeRelationManager $attendeeRelationManager
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        ObjectMapper $mapper,
        TokenStorageInterface $securityTokenStorage,
        EventDispatcherInterface $dispatcher,
        AttendeeRelationManager $attendeeRelationManager
    ) {
        parent::__construct($entityManager, $configManager, $translator, $mapper, $securityTokenStorage, $dispatcher);
        $this->attendeeRelationManager = $attendeeRelationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return parent::transform($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $entities = parent::reverseTransform($value);
        if (!$entities) {
            return $entities;
        }

        return array_map(
            function ($entity) {
                return $this->attendeeRelationManager->createAttendee($entity) ?: $entity;
            },
            $entities
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassLabel($className)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getResult($text, $object)
    {
        $result = parent::getResult($text, $object);
        $result['hidden'] = !$this->attendeeRelationManager->getRelatedEntity($object);
        $result['displayName'] = $object->getDisplayName();
        $result['email'] = $object->getEmail();
        $result['origin'] = $object->getOrigin() ? $object->getOrigin()->getId() : Attendee::ORIGIN_SERVER;

        return $result;
    }
}
