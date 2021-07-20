<?php

namespace Oro\Bundle\SegmentBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles segment form.
 */
class SegmentHandler
{
    use RequestHandlerTrait;

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

    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $managerRegistry,
        StaticSegmentManager $staticSegmentManager
    ) {
        $this->requestStack = $requestStack;
        $this->managerRegistry = $managerRegistry;
        $this->staticSegmentManager = $staticSegmentManager;
    }

    /**
     * Process form
     *
     * @param FormInterface $form
     * @param Segment $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(FormInterface $form, Segment $entity)
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
