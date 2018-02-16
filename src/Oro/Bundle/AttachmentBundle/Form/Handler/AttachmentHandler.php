<?php

namespace Oro\Bundle\AttachmentBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AttachmentHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ObjectManager */
    protected $manager;

    /**
     * @param RequestStack  $requestStack
     * @param ObjectManager $manager
     */
    public function __construct(RequestStack $requestStack, ObjectManager $manager)
    {
        $this->requestStack = $requestStack;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param FormInterface $form
     * @return bool
     */
    public function process(FormInterface $form)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $form->submit($request);
            if ($form->isValid()) {
                $this->onSuccess($form->getData());
                return true;
            }
        }

        return false;
    }

    /**
     * @param Attachment $entity
     */
    protected function onSuccess(Attachment $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
