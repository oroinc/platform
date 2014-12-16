<?php

namespace Oro\Bundle\CommentBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CommentBundle\Entity\Comment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class CommentHandler
{
    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager)
    {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param  Comment $entity
     *
     * @return bool
     */
    public function process(Comment $entity)
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
     * @param Comment $entity
     */
    protected function onSuccess(Comment $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
