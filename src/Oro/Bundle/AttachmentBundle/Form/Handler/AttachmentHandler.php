<?php

namespace Oro\Bundle\AttachmentBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

class AttachmentHandler
{
    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /**
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(Request $request, ObjectManager $manager)
    {
        $this->request = $request;
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
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $form->submit($this->request);
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
