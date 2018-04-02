<?php

namespace Oro\Bundle\NavigationBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\NavigationBundle\Entity\AbstractPageState;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PageStateHandler
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
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     *
     * @param FormInterface         $form
     * @param RequestStack          $requestStack
     * @param ObjectManager         $manager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $manager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->form = $form;
        $this->requestStack = $requestStack;
        $this->manager = $manager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Process form
     *
     * @param  AbstractPageState $entity
     * @return bool True on successfull processing, false otherwise
     */
    public function process(AbstractPageState $entity)
    {
        if ($this->tokenStorage->getToken() && is_object($user = $this->tokenStorage->getToken()->getUser())) {
            $entity->setUser($user);
        }

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
     * "Success" form handler
     *
     * @param AbstractPageState $entity
     */
    protected function onSuccess(AbstractPageState $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
