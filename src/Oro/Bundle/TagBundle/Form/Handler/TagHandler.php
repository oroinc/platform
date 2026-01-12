<?php

namespace Oro\Bundle\TagBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\TagBundle\Entity\Tag;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles form submission and processing for tag entities.
 *
 * This handler manages the complete lifecycle of tag form processing, including form data binding,
 * request validation, and persistence of tag entities to the database. It supports both POST and PUT
 * HTTP methods for creating and updating tags.
 */
class TagHandler
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

    public function __construct(FormInterface $form, RequestStack $requestStack, ObjectManager $manager)
    {
        $this->form    = $form;
        $this->requestStack = $requestStack;
        $this->manager = $manager;
    }

    /**
     * Process form
     *
     * @param  Tag  $entity
     * @return bool True on successfull processing, false otherwise
     */
    public function process(Tag $entity)
    {
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                $this->manager->persist($entity);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }
}
