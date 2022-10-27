<?php

namespace Oro\Bundle\ReportBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ReportBundle\Entity\Report;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles report form entity saving
 */
class ReportHandler
{
    use RequestHandlerTrait;

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

    /**
     * Process form
     *
     * @param Report $entity
     * @param FormInterface $form
     * @return bool  True on successful processing, false otherwise
     */
    public function process(Report $entity, FormInterface $form)
    {
        $form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request);

            if ($form->isValid()) {
                $this->onSuccess($entity);
                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     */
    protected function onSuccess(Report $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
