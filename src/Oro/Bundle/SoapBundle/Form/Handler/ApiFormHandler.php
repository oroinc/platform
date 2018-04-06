<?php

namespace Oro\Bundle\SoapBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ApiFormHandler implements FormAwareInterface
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
    protected $entityManager;

    /**
     *
     * @param FormInterface $form
     * @param RequestStack  $requestStack
     * @param ObjectManager $entityManager
     */
    public function __construct(FormInterface $form, RequestStack $requestStack, ObjectManager $entityManager)
    {
        $this->form          = $form;
        $this->requestStack  = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Process form
     *
     * @param mixed $entity
     *
     * @return mixed|null The instance of saved entity on successful processing; otherwise, null
     */
    public function process($entity)
    {
        $entity = $this->prepareFormData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                return $this->onSuccess($entity) ?: $entity;
            }
        }

        return null;
    }

    /**
     * @param mixed $entity
     *
     * @return mixed The instance of form data object
     */
    protected function prepareFormData($entity)
    {
        $this->form->setData($entity);

        return $entity;
    }

    /**
     * "Success" form handler
     *
     * @param mixed $entity
     *
     * @return mixed|null The instance of saved entity. Can be null if it is equal of the $entity argument
     */
    protected function onSuccess($entity)
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
