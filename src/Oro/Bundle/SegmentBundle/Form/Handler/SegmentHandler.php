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
        $isNewEntity = null == $entity->getId();

        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity, $isNewEntity);
                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param Segment $entity
     * @param bool $isNewEntity
     */
    protected function onSuccess(Segment $entity, $isNewEntity)
    {
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($entity);
        $entityManager->flush();

        if ($isNewEntity && $entity->isStaticType()) {
            $this->staticSegmentManager->run($entity);
        }
    }
}
