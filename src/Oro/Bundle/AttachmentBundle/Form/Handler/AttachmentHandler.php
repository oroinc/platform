<?php

namespace Oro\Bundle\AttachmentBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles form submission and persistence of attachment entities.
 *
 * This handler processes attachment forms submitted via POST or PUT requests, validates
 * the form data, and persists valid attachment entities to the database. It leverages the
 * {@see RequestHandlerTrait} to manage request handling and provides a standard interface for
 * attachment form processing in the application.
 */
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
