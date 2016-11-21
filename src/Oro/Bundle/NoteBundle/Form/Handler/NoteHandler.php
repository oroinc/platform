<?php

namespace Oro\Bundle\NoteBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
use Oro\Bundle\ActivityBundle\Event\Events;
use Oro\Bundle\NoteBundle\Entity\Note;

class NoteHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ActivityManager
     */
    protected $activityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param FormInterface            $form
     * @param Request                  $request
     * @param ManagerRegistry          $managerRegistry
     * @param ActivityManager          $activityManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $managerRegistry,
        ActivityManager $activityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->managerRegistry = $managerRegistry;
        $this->activityManager = $activityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Process form
     *
     * @param  Note $entity
     *
     * @return bool
     */
    public function process(Note $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param Note $entity
     */
    protected function onSuccess(Note $entity)
    {
        $em = $this->getEntityManager();

        $oldActivityTargets = $this->getOldNoteActivityTargets($entity, $em);
        $newActivityTargets = $entity->getActivityTargetEntities();

        foreach ($newActivityTargets as $newActivityTarget) {
            if (!in_array($newActivityTarget, $oldActivityTargets)) {
                $event = new ActivityEvent($entity, $newActivityTarget);
                $this->eventDispatcher->dispatch(Events::ADD_ACTIVITY, $event);
            }
        }
        foreach ($oldActivityTargets as $oldActivityTarget) {
            if (!in_array($oldActivityTarget, $newActivityTargets)) {
                $event = new ActivityEvent($entity, $oldActivityTarget);
                $this->eventDispatcher->dispatch(Events::REMOVE_ACTIVITY, $event);
            }
        }

        $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $em->persist($entity);
        $em->flush();
    }

    /**
     * @param Note          $note
     * @param EntityManager $entityManager
     *
     * @return array
     */
    protected function getOldNoteActivityTargets(Note $note, EntityManager $entityManager)
    {
        $oldNoteActivityTargets = [];

        $uow = $entityManager->getUnitOfWork();
        $originalEntityData = $uow->getOriginalEntityData($note);
        if ($originalEntityData) {
            $targets = $this->activityManager->getActivityTargets(Note::class);
            foreach ($targets as $targetField) {
                $originalValue = $originalEntityData[$targetField];
                if ($originalValue instanceof PersistentCollection) {
                    $oldNoteActivityTargets = array_merge($oldNoteActivityTargets, $originalValue->getSnapshot());
                }
            }
        }

        return $oldNoteActivityTargets;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(Note::class);
    }
}
