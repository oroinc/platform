<?php

namespace Oro\Bundle\SegmentBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SegmentHandler
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
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var StaticSegmentManager
     */
    protected $staticSegmentManager;

    /**
     * @param FormInterface $form
     * @param RequestStack $requestStack
     * @param ManagerRegistry $managerRegistry
     * @param StaticSegmentManager $staticSegmentManager
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        StaticSegmentManager $staticSegmentManager
    ) {
        $this->form = $form;
        $this->requestStack = $requestStack;
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
