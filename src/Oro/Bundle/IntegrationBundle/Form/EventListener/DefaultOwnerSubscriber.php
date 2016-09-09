<?php

namespace Oro\Bundle\IntegrationBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\DefaultOwnerTypeAwareInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class DefaultOwnerSubscriber implements EventSubscriberInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;
    /** @var TypesRegistry */
    protected $typesRegistry;

    /**
     * @param SecurityFacade $securityFacade
     * @param TypesRegistry  $typesRegistry
     */
    public function __construct(SecurityFacade $securityFacade, TypesRegistry $typesRegistry)
    {
        $this->securityFacade = $securityFacade;
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::PRE_SET_DATA  => 'preSet',
            FormEvents::PRE_SUBMIT    => 'preSubmit',
        ];
    }

    /**
     * Sets default data for create integrations form
     *
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data && !$data->getId() && !$data->getDefaultUserOwner() || null === $data) {
            if ($form->has('defaultUserOwner')) {
                $form->get('defaultUserOwner')->setData($this->securityFacade->getLoggedUser());
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null) {
            return;
        }

        $this->setDefaultOwnerField($event->getForm(), $data->getType());
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($data === null || !isset($data['type'])) {
            return;
        }

        $this->setDefaultOwnerField($event->getForm(), $data['type']);
    }

    /**
     * Picks proper default owner field for form, based on type.
     *
     * @param FormInterface $form
     * @param string        $type
     */
    protected function setDefaultOwnerField(FormInterface $form, $type = null)
    {
        $type = $this->typesRegistry->getDefaultOwnerType($type);

        if ($type === DefaultOwnerTypeAwareInterface::BUSINESS_UNIT) {
            $this->addBusinessUnitField($form);
        } else {
            $this->addUserOwnerField($form);
        }
    }

    /**
     * Adds business unit default owner field to form.
     *
     * @param FormInterface $form
     */
    protected function addUserOwnerField(FormInterface $form)
    {
        if ($form->has('defaultBusinessUnitOwner')) {
            $form->remove('defaultBusinessUnitOwner');
        }
        if (!$form->has('defaultUserOwner')) {
            $form->add(
                'defaultUserOwner',
                'oro_user_organization_acl_select',
                [
                    'required' => true,
                    'label'    => 'oro.integration.integration.default_user_owner.label',
                    'tooltip'  => 'oro.integration.integration.default_user_owner.description',
                    'constraints' => new NotBlank(),
                ]
            );
        }
    }

    /**
     * Adds user default owner field to form.
     *
     * @param FormInterface $form
     */
    protected function addBusinessUnitField(FormInterface $form)
    {
        if ($form->has('defaultUserOwner')) {
            $form->remove('defaultUserOwner');
        }
        if (!$form->has('defaultBusinessUnitOwner')) {
            $form->add(
                'defaultBusinessUnitOwner',
                'oro_business_unit_select',
                [
                    'required'    => true,
                    'label'       => 'oro.integration.integration.default_business_unit_owner.label',
                    'tooltip'     => 'oro.integration.integration.default_business_unit_owner.description',
                    'constraints' => new NotBlank(),
                ]
            );
        }
    }
}
