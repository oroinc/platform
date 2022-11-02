<?php

namespace Oro\Bundle\AttachmentBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AttachmentHandler
{
    use RequestHandlerTrait;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ObjectManager */
    protected $manager;

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
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                $this->onSuccess($form->getData());
                return true;
            }
        }

        return false;
    }

    protected function onSuccess(Attachment $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
