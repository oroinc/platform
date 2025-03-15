<?php

namespace Oro\Bundle\NoteBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
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

    public function __construct(
        protected FormInterface $form,
        protected RequestStack $requestStack,
        protected ManagerRegistry $doctrine,
        protected ActivityManager $activityManager
    ) {
    }

    public function process(Note $entity): bool
    {
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);
            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    protected function onSuccess(Note $entity): void
    {
        $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));

        $em = $this->doctrine->getManagerForClass(Note::class);
        $em->persist($entity);
        $em->flush();
    }
}
