<?php

namespace Oro\Bundle\CommentBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\CommentBundle\Entity\Comment;

class CommentApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     * @param ConfigManager $configManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        ConfigManager $configManager
    ) {
        $this->form          = $form;
        $this->request       = $request;
        $this->manager       = $manager;
        $this->configManager = $configManager;
    }

    /**
     * Process form
     *
     * @param Comment $entity
     *
     * @return bool
     */
    public function process(Comment $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
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
