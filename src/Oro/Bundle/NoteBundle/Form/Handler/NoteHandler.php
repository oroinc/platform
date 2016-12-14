<?php

namespace Oro\Bundle\NoteBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
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
     * @param FormInterface   $form
     * @param Request         $request
     * @param ManagerRegistry $managerRegistry
     * @param ActivityManager $activityManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $managerRegistry,
        ActivityManager $activityManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->managerRegistry = $managerRegistry;
        $this->activityManager = $activityManager;
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
        if ($this->form->has('contexts')) {
            $contexts = $this->form->get('contexts')->getData();
            $this->activityManager->setActivityTargets($entity, $contexts);
        }
        $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $em->persist($entity);
        $em->flush();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(Note::class);
    }
}
