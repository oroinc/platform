<?php

namespace Oro\Bundle\ReminderBundle\Doctrine\EventListener;

use Symfony\Component\Security\Core\Util\ClassUtils;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;

use Oro\Bundle\ReminderBundle\Doctrine\RemindersLazyLoadCollection;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;

class RemindableSubscriber implements EventSubscriber
{

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            // @codingStandardsIgnoreStart
            Events::postLoad
            // @codingStandardsIgnoreEnd
        );
    }

    /**
     * After entity with reminders was loaded, set lazy load collection
     *
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof RemindableInterface) {
            $entityManager = $args->getEntityManager();
            $repository = $entityManager->getRepository('OroReminderBundle:Reminder');
            $className = ClassUtils::getRealClass($entity);
            $identifier = $entityManager->getClassMetadata($className)->getIdentifierValues($entity);
            $entity->setReminders(new RemindersLazyLoadCollection($repository, $className, $identifier));
        }
    }
}
