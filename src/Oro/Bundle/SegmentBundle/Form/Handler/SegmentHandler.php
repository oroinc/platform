<?php

namespace Oro\Bundle\SegmentBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class SegmentHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var StaticSegmentManager
     */
    protected $staticSegmentManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ManagerRegistry $managerRegistry
     * @param StaticSegmentManager $staticSegmentManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $managerRegistry,
        StaticSegmentManager $staticSegmentManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->managerRegistry = $managerRegistry;
        $this->staticSegmentManager = $staticSegmentManager;
    }

    /**
     * Process form
     *
     * @param  Segment $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(Segment $entity)
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
     * @param Segment $entity
     */
    protected function onSuccess(Segment $entity)
    {
        $entityManager = $this->managerRegistry->getManager();

        $isNewEntity = is_null($entity->getId());
        if ($isNewEntity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        if ($isNewEntity && $entity->isStaticType()) {
            $this->staticSegmentManager->run($entity);
        }
    }
}
