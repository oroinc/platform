<?php

namespace Oro\Bundle\IntegrationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ChannelHandler
{
    use RequestHandlerTrait;

    const UPDATE_MARKER = 'formUpdateMarker';

    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityManager */
    protected $em;

    /** @var FormInterface */
    protected $form;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param RequestStack             $requestStack
     * @param FormInterface            $form
     * @param EntityManager            $em
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        RequestStack $requestStack,
        FormInterface $form,
        EntityManager $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->requestStack    = $requestStack;
        $this->form            = $form;
        $this->em              = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Process form
     *
     * @param Integration $entity
     *
     * @return bool
     */
    public function process(Integration $entity)
    {
        $userOwner   = $entity->getDefaultUserOwner();
        $isNewEntity = !$entity->getId();
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $oldState = $this->getIntegration($entity);
            // Determines whether we have to submit form as if a button was clicked (==false)
            // or user just changed Type (==true).
            $updateMarker = $request->get(self::UPDATE_MARKER, false);

            // We must not clear missing values if it is just a form update, i.e ($updateMarker == true),
            // because we will lose default values of underlying entity.
            // Otherwise, if it just a normal submit, we have to submit form in a normal way.
            $this->submitPostPutRequest($this->form, $request, !$updateMarker);
            if (!$updateMarker && $this->form->isValid()) {
                $this->em->persist($entity);
                $this->em->flush();

                if (!$isNewEntity && null === $userOwner && $userOwner !== $entity->getDefaultUserOwner()) {
                    $this->eventDispatcher->dispatch(DefaultOwnerSetEvent::NAME, new DefaultOwnerSetEvent($entity));
                }

                if (!$isNewEntity && $oldState) {
                    $this->eventDispatcher->dispatch(
                        IntegrationUpdateEvent::NAME,
                        new IntegrationUpdateEvent($entity, $oldState)
                    );
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param Integration $integration
     * @return Integration|null
     */
    protected function getIntegration(Integration $integration)
    {
        if (!$integration->getId()) {
            return null;
        }

        $oldIntegration = $this->em->find('OroIntegrationBundle:Channel', $integration->getId());

        return $oldIntegration ? clone $oldIntegration : null;
    }
}
