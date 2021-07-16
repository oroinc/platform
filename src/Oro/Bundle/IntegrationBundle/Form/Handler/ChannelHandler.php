<?php

namespace Oro\Bundle\IntegrationBundle\Form\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Event\DefaultOwnerSetEvent;
use Oro\Bundle\IntegrationBundle\Event\IntegrationUpdateEvent;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This handler is submitting and saving the data when creating or updating integration channel
 * or recreating the integration channel form when changing integration channel or transport type
 */
class ChannelHandler
{
    use RequestHandlerTrait;

    const UPDATE_MARKER = 'formUpdateMarker';

    const TRANSPORT_TYPE_FIELD_NAME = 'transportType';

    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityManager */
    protected $em;

    /** @var FormInterface */
    protected $form;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var FormFactoryInterface */
    protected $formFactory;

    public function __construct(
        RequestStack $requestStack,
        FormInterface $form,
        EntityManager $em,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory
    ) {
        $this->requestStack = $requestStack;
        $this->form = $form;
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->formFactory = $formFactory;
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
        $userOwner = $entity->getDefaultUserOwner();
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            // Determines whether we have to submit form as if a button was clicked (==false)
            // or user just changed Type (==true).
            $updateMarker = $request->get(self::UPDATE_MARKER, false);

            // We must not clear missing values if it is just a form update, i.e ($updateMarker == true),
            // because we will lose default values of underlying entity.
            // Otherwise, if it just a normal submit, we have to submit form in a normal way.
            $this->submitPostPutRequest($this->form, $request, !$updateMarker);

            if ($updateMarker) {
                $this->updateForm($entity);
            } elseif ($this->form->isValid()) {
                $this->saveFormData($entity, $userOwner);

                return true;
            }
        }

        return false;
    }

    private function updateForm(Integration $entity)
    {
        // recreate form due to JS validation should be shown even in case when it was not validated on backend
        $this->form = $this->formFactory
            ->createNamed(
                $this->form->getName(),
                ChannelType::class,
                $entity
            );

        $request = $this->requestStack->getCurrentRequest();
        $updateMarker = $request->get(self::UPDATE_MARKER, false);

        // Form should be resubmitted in case of switching transport type in order for form listeners
        // to fire and add the dynamic fields for the transport type
        if ($updateMarker === sprintf('%s[%s]', $this->form->getName(), self::TRANSPORT_TYPE_FIELD_NAME)) {
            $this->submitPostPutRequest($this->form, $request, !$updateMarker);
        }
    }

    private function saveFormData(Integration $entity, User $userOwner = null)
    {
        $isNewEntity = !$entity->getId();
        $oldState = $this->getIntegration($entity);

        $this->em->persist($entity);
        $this->em->flush();

        if (!$isNewEntity && null === $userOwner) {
            $this->eventDispatcher->dispatch(new DefaultOwnerSetEvent($entity), DefaultOwnerSetEvent::NAME);
        }

        if (!$isNewEntity && $oldState) {
            $this->eventDispatcher->dispatch(
                new IntegrationUpdateEvent($entity, $oldState),
                IntegrationUpdateEvent::NAME
            );
        }
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

    /**
     * Returns form instance
     *
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }
}
