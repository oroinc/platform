<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles form processing for API requests.
 *
 * This handler processes forms submitted via API endpoints (POST/PUT requests),
 * validates the form data, and persists valid entities to the database. It provides
 * a simplified form processing workflow optimized for API usage without event dispatching.
 */
class ApiFormHandler
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

    public function __construct(RequestStack $requestStack, ObjectManager $manager)
    {
        $this->requestStack = $requestStack;
        $this->manager = $manager;
    }

    public function setForm(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * Process form
     *
     * @param  object $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process($entity)
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
     * "Success" form handler
     *
     * @param object $entity
     */
    protected function onSuccess($entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
