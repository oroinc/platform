<?php

namespace Oro\Bundle\NoteBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This handler is setting updated date for notes
 */
class NoteHandler
{
    use RequestHandlerTrait;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var RequestStack
     */
    protected $requestStack;

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
     * @param RequestStack    $requestStack
     * @param ManagerRegistry $managerRegistry
     * @param ActivityManager $activityManager
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        ActivityManager $activityManager
    ) {
        $this->form = $form;
        $this->requestStack = $requestStack;
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

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);

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
