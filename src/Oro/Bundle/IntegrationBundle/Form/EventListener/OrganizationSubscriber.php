<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrganizationSubscriber implements EventSubscriberInterface
{
    /** @var ObjectManager  */
    protected $entityManager;

    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::POST_SET_DATA => 'postSet'];
    }

    /**
     * Sets default data for create channels form
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data && !$data->getId() && !$data->getOrganization() || null === $data) {
            $organizations = $this->entityManager->getRepository('OroOrganizationBundle:Organization')->findAll();

            if (count($organizations) === 1) {
                $modifier = $this->addOrganizationFieldClosure();
                $modifier($form, true);
            } else {
                $modifier = $this->addOrganizationFieldClosure();
                $modifier($form, false);
            }

            if ($form->has('organization')) {
                $form->get('organization')->setData($organizations[0]);
            }
        }
    }

    /**
     * @return \Closure
     */
    protected function addOrganizationFieldClosure()
    {
        return function (FormInterface $form, $isHidden) {
            $form->add(
                'organization',
                'oro_organization_select',
                [
                    'required' => true,
                    'label'    => 'oro.integration.integration.organization.label',
                    'tooltip'  => 'oro.integration.integration.organization.tooltip',
                    'attr'     => $isHidden ? ['class' => 'hide'] : [],
                ]
            );
        };
    }
}
